=== Math Chat Render ===
Contributors: Leonardo Lima
Donate link:  https://www.paypal.com/invoice/p/#FPHZV27JRERTAFZX or email leo_lima007@hotmail.com
Tags: mathjax, latex, ai engine, chat, chatbot, equations, math, formulas, render, math
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.2.1
Requires PHP: 7.0
Requires Plugins: ai-engine
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Renderiza LaTeX (MathJax) em tempo real nas janelas de chat do AI Engine (MWAI).

== Description ==

Este plugin é um "add-on" (complemento) para o popular plugin AI Engine (MWAI Chatbot).

O objetivo dele é simples: fazer com que as fórmulas matemáticas (LaTeX) enviadas pelo chatbot sejam renderizadas corretamente em tempo real.

Por defeito, se o AI Engine responder com [ x = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a} ], o utilizador verá apenas esse texto. Com este plugin ativado, esse texto será transformado numa fórmula bonita e legível, graças ao MathJax.

O plugin deteta automaticamente quando o chatbot adiciona novas mensagens e manda o MathJax renderizá-las, incluindo mensagens que chegam via "streaming".

== Installation ==

Requisito Obrigatório: Tem de ter o plugin AI Engine (de Jordy Meow) instalado e ativo.

Instale este plugin da forma normal:

Envie a pasta mathChatRender para o diretório /wp-content/plugins/.

Ou envie o ficheiro mathChatRender.zip através do menu 'Plugins > Adicionar Novo > Enviar plugin' no seu painel do WordPress.

Ative o plugin 'Math Chat Render' através do menu 'Plugins' no WordPress.

É isso! Não há configurações. O plugin detetará automaticamente o AI Engine e o MathJax.

== Changelog ==

= 1.1.0 =

Release de produção. Remove todos os logs de console (console.log) usados na depuração.

Mantém console.error para relatar erros reais, caso ocorram.

Melhora a lógica de "pausar/retomar" o observador para estabilidade.

= 1.0.3 =

Adiciona suporte para delimitadores não-padrão [ ... ] (display) e ( ... ) (inline), que são comummente enviados por modelos de IA.

= 1.0.0 =

Lançamento inicial e versões de desenvolvimento.

Implementa o MutationObserver para detetar novas mensagens.

Implementa o debounce para lidar com o "streaming" de respostas.

= 1.2.1 =

Melhorias de compatibilidade e segurança:

- Usa um handle de script próprio (`mcr-mathjax`) ao enfileirar o MathJax para reduzir a possibilidade de conflitos com outros plugins.
- A configuração inline do MathJax foi movida para um nowdoc para evitar a interpolação acidental de `$` ou escapes pelo PHP.
- O handler JavaScript adiciona um pequeno polling para aguardar `MathJax.startup.promise` caso o runtime demore a inicializar.

Testes e instruções rápidas:

1. Ative o plugin em `wp-admin`.
2. Abra uma página que contenha o chat do AI Engine.
3. No DevTools (Console/Network): verifique se o CDN do MathJax (`tex-mml-chtml.js`) é carregado e se não há erros relacionados a MathJax.
4. Envie/visualize mensagens com LaTeX (por exemplo `$x^2$` e `$$\int_0^1 x dx$$`) e verifique se são renderizadas.

Se houver problemas, consulte o console para mensagens do `MathChatRender:` que ajudam a diagnosticar (por exemplo: se o container `.mwai-chatbot-container` não for encontrado).