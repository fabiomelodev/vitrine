<x-site.layout title="Termos de uso">
    <article class="container legal">
        <h1 class="page-title" style="font-size: clamp(34px, 4vw, 48px)">Termos de <span class="serif">uso</span></h1>
        <p class="muted">Última atualização: [DATA]</p>
        <div class="notice notice-amber">Modelo inicial. Revise com um advogado e preencha os campos entre colchetes antes de publicar.</div>

        <h2>1. Quem somos</h2>
        <p>O Provador é uma plataforma de catálogo online{{ \App\Support\Platform::operatedBy() }}, com sede em [CIDADE/UF] ("Provador", "nós"). Contato: [E-MAIL DE SUPORTE].</p>

        <h2>2. O que o Provador oferece</h2>
        <p>Permitimos que lojistas criem uma vitrine online, cadastrem produtos e recebam pedidos enviados pelos clientes via WhatsApp. O Provador <strong>não processa pagamentos, não entrega produtos e não é parte da venda</strong>: preço, estoque, pagamento, entrega, trocas e garantias são combinados diretamente entre o lojista e o cliente.</p>

        <h2>3. Cadastro e conta</h2>
        <p>Para usar o painel, o lojista deve informar dados verdadeiros e manter a senha em sigilo. O lojista é responsável por todas as ações feitas com sua conta e pelos usuários que convidar para a loja.</p>

        <h2>4. Período de teste, assinatura e cancelamento</h2>
        <p>Lojas novas têm um período de teste gratuito, informado no momento do cadastro. Após o teste, o uso depende de assinatura mensal, cobrada por meio do nosso parceiro de pagamentos (Asaas) via Pix, boleto ou cartão. O valor vigente é exibido no site e no painel.</p>
        <p>Em caso de atraso, a vitrine pode sair do ar após o prazo de tolerância informado no painel. O lojista pode cancelar a qualquer momento pelo painel; a vitrine permanece no ar até o fim do período já pago. Não há reembolso proporcional de períodos já iniciados, salvo previsão legal.</p>

        <h2>5. Responsabilidades do lojista</h2>
        <p>O lojista é o único responsável pelos produtos anunciados, pelas fotos e textos publicados, pela veracidade de preços e disponibilidade, pelo atendimento aos clientes e pelo cumprimento do Código de Defesa do Consumidor e demais leis aplicáveis. É proibido anunciar produtos ilícitos, falsificados ou que violem direitos de terceiros.</p>

        <h2>6. Dados dos clientes da loja</h2>
        <p>Os dados que os clientes informam ao enviar um pedido (nome e WhatsApp) são tratados pelo lojista, que é o controlador desses dados. O Provador atua como operador, armazenando-os para exibição no painel da loja, conforme a <a href="{{ url('privacidade') }}">Política de privacidade</a>.</p>

        <h2>7. Disponibilidade e limites</h2>
        <p>Buscamos manter o serviço disponível, mas podem ocorrer interrupções para manutenção ou por fatores fora do nosso controle. O plano pode ter limites de uso (como número de produtos), informados no painel.</p>

        <h2>8. Suspensão</h2>
        <p>Podemos suspender lojas que violem estes termos, a lei ou que coloquem em risco a plataforma ou terceiros.</p>

        <h2>9. Alterações</h2>
        <p>Estes termos podem ser atualizados. Mudanças relevantes serão comunicadas por e-mail ou no painel.</p>

        <h2>10. Foro</h2>
        <p>Fica eleito o foro da comarca de [CIDADE/UF], salvo disposição legal em contrário.</p>
    </article>
</x-site.layout>
