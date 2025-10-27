# Filtros PHP disponíveis em Math Chat Render

Este arquivo descreve os filtros PHP expostos pelo plugin `Math Chat Render` para permitir customização sem editar o código-fonte principal.

1) `mcr_should_enqueue`
- Uso: `add_filter( 'mcr_should_enqueue', '__return_false' );`
- Propósito: permite que um tema ou outro plugin impeça completamente que o plugin enfileire qualquer comportamento (útil para ambientes que já carregam MathJax por conta própria).

2) `mcr_load_mathjax`
- Uso: `add_filter( 'mcr_load_mathjax', '__return_false' );`
- Propósito: controla se o plugin deve carregar o MathJax via CDN. Se retornar `false`, o plugin não enfileira o script CDN. Ainda assim, o `handler` do plugin é enfileirado e a configuração é injetada.

3) `mcr_config`
- Uso: `add_filter( 'mcr_config', function( $defaults ) { $defaults['debug'] = 1; return $defaults; } );`
- Propósito: permite sobrescrever os valores de configuração que são injetados no frontend como `globalThis.mcrConfig` (por exemplo `debug` e `enable_delimiters`). Recebe um array associativo com valores defaults e deve retornar o array modificado.

4) `mcr_mathjax_sri`
- Uso: `add_filter( 'mcr_mathjax_sri', function(){ return 'sha384-...'; } );`
- Propósito: Retorna a string SRI (subresource integrity) a ser aplicada ao handle do MathJax quando o CDN é usado. Se vazio (padrão), nenhum atributo `integrity` será adicionado.

Exemplo mínimo (functions.php do tema):

```php
// Desativa carregamento do MathJax pelo plugin (use sua própria cópia)
add_filter( 'mcr_load_mathjax', '__return_false' );

// Ativa debug via filtro
add_filter( 'mcr_config', function( $cfg ) {
    $cfg['debug'] = 1;
    return $cfg;
});
```

Observações
- As alterações via filtro são aplicadas imediatamente para novas requisições de página. Se alterar filtros em tempo de execução em uma sessão já carregada no navegador, será necessário recarregar a página para que o novo enqueue/inline script entre em vigor.
- Para aplicar mudanças de configuração no frontend sem reload, considere implementar um endpoint REST que retorne `mcr_config` e um método frontend para `fetch()` e reaplicar `MathJax.typesetPromise()` — isso não é feito por padrão para manter o plugin simples.

Arquivo: `mathRender.php` — filtros referenciados: `mcr_should_enqueue`, `mcr_load_mathjax`, `mcr_config`, `mcr_mathjax_sri`.
