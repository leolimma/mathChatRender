/**
 * Math Chat Render (mcr-handler) - v1.1.0 (Produção)
 *
 * Lógica de Observador Pausado (Disconnect/Reconnect).
 * Remove todos os console.log() de depuração.
 * Mantém console.error() para relatar erros reais.
 */
document.addEventListener('DOMContentLoaded', (event) => {

    // 1. Encontrar o container RAIZ
    const chatRootContainer = document.querySelector('.mwai-chatbot-container');

    // 2. Sair cedo se o MathJax ou o container raiz não estiverem lá
    if (!chatRootContainer || typeof MathJax === 'undefined') {
        if (!chatRootContainer) {
            console.error('MathChatRender: Container RAIZ (.mwai-chatbot-container) não encontrado.');
        }
        if (typeof MathJax === 'undefined') {
            console.error('MathChatRender: Biblioteca MathJax não foi carregada.');
        }
        return;
    }

    /**
     * Função utilitária de Debounce.
     */
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    /**
     * Esta função força o scroll de um elemento para o fundo.
     */
    function forceScrollToBottom(element) {
        if (element) {
            // Atraso de 100ms para garantir que o DOM tem a nova scrollHeight
            setTimeout(() => {
                element.scrollTop = element.scrollHeight;
            }, 100); 
        }
    }

    /**
     * Esta função anexa o observador de mensagens ao elemento de conversa.
     */
    function attachMessageObserver(conversationElement) {
        const chatBody = conversationElement.closest('.mwai-body');
        
        // As opções que o nosso observador usa
        const observerConfig = {
            childList: true,      
            subtree: true,        
            characterData: true   
        };
        
        let messageObserver; // Declarado aqui para ser acessível por ambas as funções

        const renderLastMessageAndScroll = () => {
            const lastReply = conversationElement.querySelector('.mwai-reply:last-child');
            if (!lastReply) return;

            // 1. PAUSAR O OBSERVADOR
            messageObserver.disconnect(); 

            // 2. RENDERIZAR
            MathJax.typesetPromise([lastReply])
                .then(() => {
                    // 3. FORÇAR SCROLL
                    forceScrollToBottom(chatBody);
                })
                .catch((err) => {
                    console.error('MathChatRender: Erro ao renderizar MathJax:', err);
                })
                .finally(() => {
                    // 4. RE-INICIAR O OBSERVADOR
                    messageObserver.observe(conversationElement, observerConfig);
                });
        };

        // Cria a versão "debounced" da nossa função.
        const debouncedRender = debounce(renderLastMessageAndScroll, 250);

        // 3. O Observador de Mensagens
        messageObserver = new MutationObserver((mutationsList) => {
            debouncedRender();
        });
        
        // --- LÓGICA DO HISTÓRICO ---
        
        // Pausa o observador ANTES de mexer no histórico
        messageObserver.disconnect();

        MathJax.typesetPromise([conversationElement])
            .then(() => {
                forceScrollToBottom(chatBody);
            })
            .catch((err) => console.error('MathChatRender: Erro ao renderizar histórico:', err))
            .finally(() => {
                // Re-inicia o observador DEPOIS do histórico estar pronto
                messageObserver.observe(conversationElement, observerConfig);
            });
    }

    /**
     * Esta função procura por .mwai-conversation dentro dos nós adicionados.
     */
    function findConversationElement(nodes) {
        for (const node of nodes) {
            if (node.nodeType === 1) { // É um elemento
                if (node.matches('.mwai-conversation')) {
                    return node; // Encontramos
                }
                const childTarget = node.querySelector('.mwai-conversation');
                if (childTarget) {
                    return childTarget; // Encontramos
                }
            }
        }
        return null;
    }

    // --- Lógica Principal ---

    // 7. Tenta encontrar o .mwai-conversation imediatamente.
    let conversationElement = chatRootContainer.querySelector('.mwai-conversation');

    if (conversationElement) {
        attachMessageObserver(conversationElement);
    } else {
        // 8. O Observador Mestre
        const masterObserver = new MutationObserver((mutationsList, observer) => {
            for (const mutation of mutationsList) {
                if (mutation.addedNodes.length > 0) {
                    const foundTarget = findConversationElement(mutation.addedNodes);
                    
                    if (foundTarget) {
                        // 9. ENCONTRAMOS!
                        attachMessageObserver(foundTarget);
                        observer.disconnect();
                        return; 
                    }
                }
            }
        });

        // 10. Inicia o Observador Mestre
        masterObserver.observe(chatRootContainer, {
            childList: true, 
            subtree: true
        });
    }
});

