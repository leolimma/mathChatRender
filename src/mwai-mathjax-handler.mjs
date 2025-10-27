// Modern ES module entry for Math Chat Render handler
// Uses top-level await to wait for MathJax startup before attaching observers.

// Utilities moved to top-level
function mutationContainsMath(mutation) {
  if (mutation.addedNodes?.length) {
    for (const node of mutation.addedNodes) {
      if (!node) continue;
      if (node.nodeType === 1) {
        if (node.matches?.('.mwai-reply')) return true;
        if (node.querySelector?.('.mwai-reply')) return true;
        const text = node.textContent || '';
        if (/\$|\\\(|\\\[/.test(text)) return true;
      }
      if (node.nodeType === 3) {
        if (/\$|\\\(|\\\[/.test(node.data || '')) return true;
      }
    }
  }
  if (mutation.type === 'characterData') {
    const data = mutation.target?.data || '';
    if (/\$|\\\(|\\\[/.test(data)) return true;
  }
  return false;
}

function findConversationElement(nodes) {
  for (const node of nodes) {
    if (node.nodeType === 1) {
      if (node.matches?.('.mwai-conversation')) return node;
      const childTarget = node.querySelector?.('.mwai-conversation');
      if (childTarget) return childTarget;
    }
  }
  return null;
}

function safeDisconnect(observer) {
  try {
    if (observer && typeof observer.disconnect === 'function') observer.disconnect();
  } catch (err) {
    console.warn('MathChatRender: erro ao desconectar observer', err);
  }
}

// Expose API via globalThis
globalThis.mcr = globalThis.mcr || {};
globalThis.mcrConfig = globalThis.mcrConfig || {};

globalThis.mcr.setConfig = function(cfg) {
  if (typeof cfg === 'object') {
    globalThis.mcrConfig = Object.assign(globalThis.mcrConfig || {}, cfg);
  }
};

globalThis.mcr.typeset = function(node) {
  return new Promise((resolve, reject) => {
    if (globalThis.MathJax && MathJax.typesetPromise) {
      MathJax.typesetPromise([node]).then(resolve).catch(reject);
    } else {
      reject(new Error('MathJax not ready'));
    }
  });
};

globalThis.mcr.refresh = function() {
  const root = document.querySelector('.mwai-chatbot-container');
  if (!root) return Promise.resolve();
  const conv = root.querySelector('.mwai-conversation');
  if (!conv) return Promise.resolve();
  return globalThis.mcr.typeset(conv);
};

// waitForMathJaxStartup (same logic as before)
function waitForMathJaxStartup(timeout = 5000, interval = 100) {
  return new Promise((resolve, reject) => {
    const maxAttempts = Math.ceil(timeout / interval);
    let attempts = 0;
    const timer = setInterval(() => {
      attempts++;
      if (globalThis.MathJax && MathJax.startup && MathJax.startup.promise) {
        clearInterval(timer);
        resolve();
      } else if (attempts >= maxAttempts) {
        clearInterval(timer);
        reject(new Error('MathJax startup não encontrado dentro do timeout'));
      }
    }, interval);
  });
}

// Debounce factory
function debounceFactory() {
  let debounceTimer = null;
  return function(func, delay) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(func, delay);
  };
}

const debounce = debounceFactory();

function forceScrollToBottom(element) {
  if (!element) return;
  requestAnimationFrame(() => {
    try {
      element.scrollTop = element.scrollHeight;
    } catch (err) {
      console.warn('MathChatRender: erro ao forçar scroll', err);
    }
  });
}

async function renderLastMessageAndScroll(chatBody, messageObserverRef) {
  const lastReply = chatBody.querySelector('.mwai-reply:last-child');
  if (!lastReply) return;

  safeDisconnect(messageObserverRef.current);

  try {
    await globalThis.MathJax.typesetPromise([lastReply]);
    forceScrollToBottom(chatBody);
  } catch (err) {
    console.error('MathChatRender: Erro ao renderizar MathJax:', err);
  } finally {
    if (messageObserverRef.current) {
      messageObserverRef.current.observe(chatBody, {
        childList: true,
        subtree: true,
        characterData: true
      });
    }
  }
}

function attachMessageObserver(conversationElement) {
  console.log('MathChatRender: .mwai-conversation encontrado! Anexando observador de mensagens.');

  const chatBody = conversationElement.closest('.mwai-body') || document.querySelector('.mwai-chat .mwai-body');
  const observerConfig = { childList: true, subtree: true, characterData: true };

  const messageObserverRef = { current: null };
  messageObserverRef.current = new MutationObserver((mutationsList) => {
    const shouldRun = mutationsList.some(mutationContainsMath);
    if (shouldRun) {
      debounce(() => renderLastMessageAndScroll(chatBody, messageObserverRef), 250);
    }
  });

  // Stop observing while processing history
  safeDisconnect(messageObserverRef.current);

  (async () => {
    try {
      await globalThis.MathJax.typesetPromise([conversationElement]);
      forceScrollToBottom(chatBody);
    } catch (err) {
      console.log('MathChatRender: Erro ao renderizar histórico:', err);
    } finally {
      if (messageObserverRef.current) messageObserverRef.current.observe(conversationElement, observerConfig);
    }
  })();

  globalThis.addEventListener('pagehide', () => safeDisconnect(messageObserverRef.current));
}

// Main (top-level await)
await waitForMathJaxStartup();
if (!globalThis.MathJax?.startup?.promise) {
  console.error('MathChatRender: MathJax startup não encontrado no tempo limite.');
} else {
  await globalThis.MathJax.startup.promise;
  console.log('MathChatRender: MathJax v4 iniciado. A executar o observador do chat.');

  function init() {
    const chatRootContainer = document.querySelector('.mwai-chatbot-container');
    if (!chatRootContainer) {
      console.error('MathChatRender: Container RAIZ (.mwai-chatbot-container) não encontrado.');
      return;
    }

    const conversationElement = chatRootContainer.querySelector('.mwai-conversation');
    if (conversationElement) {
      attachMessageObserver(conversationElement);
      return;
    }

    console.log('MathChatRender: .mwai-conversation não encontrado. Aguardando ser adicionado ao DOM...');
    const masterObserver = new MutationObserver((mutationsList, observer) => {
      for (const mutation of mutationsList) {
        if (mutation.addedNodes?.length) {
          const foundTarget = findConversationElement(mutation.addedNodes);
          if (foundTarget) {
            attachMessageObserver(foundTarget);
            safeDisconnect(observer);
            return;
          }
        }
      }
    });

    masterObserver.observe(chatRootContainer, { childList: true, subtree: true });
    globalThis.addEventListener('pagehide', () => safeDisconnect(masterObserver));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}
