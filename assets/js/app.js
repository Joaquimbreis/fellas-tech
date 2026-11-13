/* Comportamentos da loja Fellas Tech (JavaScript puro; só usa o Bootstrap para fechar alertas) */
document.addEventListener('DOMContentLoaded', () => {
    // 1) Confirmação antes de ações destrutivas: <form data-confirmar="mensagem">
    // 2) Evita envio duplo: desativa o botão enquanto o formulário é enviado
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            const mensagem = form.dataset.confirmar;
            if (mensagem && !window.confirm(mensagem)) {
                evento.preventDefault();
                return;
            }
            const botao = form.querySelector('button[type="submit"]');
            if (botao && !evento.defaultPrevented) {
                botao.dataset.textoOriginal = botao.textContent;
                botao.disabled = true;
                botao.textContent = 'Aguarde...';
            }
        });
    });

    // Ao voltar pelo botão "Voltar" do navegador, restaura os botões desativados
    window.addEventListener('pageshow', (evento) => {
        if (!evento.persisted) {
            return;
        }
        document.querySelectorAll('button[data-texto-original]').forEach((botao) => {
            botao.disabled = false;
            botao.textContent = botao.dataset.textoOriginal;
        });
    });

    // 3) Mensagens de sucesso e informação somem sozinhas depois de alguns segundos
    document.querySelectorAll('.alert-success.alert-dismissible, .alert-info.alert-dismissible').forEach((alerta) => {
        setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(alerta).close();
        }, 6000);
    });
});
