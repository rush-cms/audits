<?php

return [
    'title' => 'Relatório de Auditoria de Performance',
    'generated_at' => 'Gerado em',
    'score' => 'Pontuação de Performance',
    'out_of' => 'de 100',
    'core_web_vitals' => 'Core Web Vitals',
    'seo' => 'SEO',
    'accessibility' => 'Acessibilidade',
    'no_issues' => 'Nenhum problema encontrado',
    'screenshot_unavailable' => 'Prévia do site indisponível - o site pode ter restrições de segurança',
    'report' => 'Relatório',
    'cta_text' => 'Fale Comigo',

    'lcp' => 'LCP',
    'lcp_full' => 'Largest Contentful Paint',
    'lcp_desc' => 'Mede a performance de carregamento. Deve ocorrer em até 2.5 segundos.',

    'fcp' => 'FCP',
    'fcp_full' => 'First Contentful Paint',
    'fcp_desc' => 'Marca quando o primeiro texto ou imagem é renderizado. Bons scores são abaixo de 1.8 segundos.',

    'cls' => 'CLS',
    'cls_full' => 'Cumulative Layout Shift',
    'cls_desc' => 'Mede estabilidade visual. Páginas devem manter CLS de 0.1 ou menos.',

    'good' => 'Bom',
    'needs_improvement' => 'Precisa Melhorar',
    'poor' => 'Ruim',
    'excellent' => 'Excelente',
    'average' => 'Médio',

    'what_means' => 'O que essas métricas significam?',
    'powered_by' => 'Powered by',
    'audit_id' => 'ID da Auditoria',
    'data_from' => 'Dados do Google PageSpeed Insights',

    'messages' => [
        'very_poor' => '7 em cada 10 visitantes abandonam seu site antes de ver seu conteúdo principal. Você está perdendo leads qualificados porque seu site demora para mostrar o que importa. Cada segundo a mais significa clientes indo para a concorrência',
        'poor' => 'Seu conteúdo principal demora para aparecer, causando 40% mais abandono de página. Visitantes estão clicando no botão "voltar" antes de conhecer seus produtos ou serviços. Isso está matando conversões',
        'excellent' => 'Excelente! Seu conteúdo principal carrega rapidamente, mantendo os visitantes engajados',
        'lcp_very_poor' => '7 em cada 10 visitantes abandonam seu site antes de ver seu conteúdo principal. Você está perdendo leads qualificados porque seu site demora para mostrar o que importa. Cada segundo a mais significa clientes indo para a concorrência',
        'lcp_poor' => 'Seu conteúdo principal demora para aparecer, causando 40% mais abandono de página. Visitantes estão clicando no botão "voltar" antes de conhecer seus produtos ou serviços. Isso está matando suas conversões',
        'lcp_excellent' => 'Excelente! Seu conteúdo principal carrega rapidamente, mantendo os visitantes engajados',
        'fcp_very_poor' => 'A primeira impressão está custando caro: visitantes veem uma tela em branco por tempo demais e assumem que seu site travou. Você está perdendo dinheiro em vendas por mês só porque não aparece nada na tela rápido o suficiente',
        'fcp_poor' => 'Seu site demora para dar o primeiro sinal de vida. 53% dos usuários mobile abandonam sites que demoram mais de 3 segundos. Você está na linha tênue entre converter ou perder o cliente',
        'fcp_excellent' => 'Ótimo! Seu site responde rapidamente, transmitindo profissionalismo desde o primeiro segundo',
        'cls_very_poor' => 'Botões que se movem = clientes frustrados. Seu site está "pulando" durante o carregamento, fazendo visitantes clicarem no lugar errado. Isso gera desconfiança e abandono imediato. Sites instáveis convertem até 70% menos',
        'cls_poor' => 'Elementos se movendo na tela irritam visitantes e prejudicam a experiência. Google penaliza sites instáveis no ranqueamento. Você está perdendo posições para concorrentes com sites mais estáveis',
        'cls_excellent' => 'Perfeito! Seu site oferece uma experiência visual estável e profissional',
    ],

    'closing' => [
        'critical' => [
            'headline' => 'Seu site está trabalhando contra você',
            'body' => 'Não é culpa sua. A maioria dos sites nascem rápidos e vão ficando lentos conforme crescem. O problema é que cada segundo perdido representa oportunidades reais escorregando pelos dedos. Enquanto você lê isso, visitantes já desistiram do seu site e foram comprar do concorrente.',
            'stats' => 'Sites com performance ruim como a atual convertem até 73% menos que sites otimizados. Se você recebe 1.000 visitantes/mês, está literalmente jogando fora centenas de potenciais clientes.',
            'solution' => 'A boa notícia? Performance não é sorte, é técnica. E diferente de marketing que demora meses para dar resultado, otimização de performance entrega resultados imediatos.',
            'cta' => 'Quer descobrir quanto dinheiro você está deixando na mesa? Vamos conversar sem compromisso.',
        ],
        'medium' => [
            'headline' => 'Você está quase lá',
            'body' => 'Seu site não está ruim, mas está na zona de perigo. Sabe aquela sensação de "poderia estar melhor"? Seus visitantes sentem isso também. E no mundo digital, "quase bom" significa "ainda não comprei" ou "ainda não contratei".',
            'stats' => 'A diferença entre um site mediano e um site rápido pode significar 40% mais conversões. É literalmente a diferença entre crescer ou estagnar.',
            'solution' => 'Você não precisa refazer tudo. Com ajustes estratégicos nas áreas certas (destacadas em vermelho e laranja acima), seu site sai da média e entra no time dos que realmente vendem.',
            'cta' => 'Vamos identificar os 3 ajustes que vão trazer o maior impacto no seu negócio?',
        ],
        'good' => [
            'headline' => 'Você está no caminho certo',
            'body' => 'Parabéns por ter um site rápido! Você já está na frente de 90% dos concorrentes. Mas no mercado digital, ficar parado é retroceder. Sites e tecnologias evoluem, e o que é rápido hoje pode ficar lento amanhã.',
            'stats' => null,
            'solution' => 'Continue monitorando mensalmente. Pequenas mudanças (novo plugin, mais imagens, mais tráfego) podem derrubar a performance sem você perceber.',
            'cta' => 'Quer garantir que seu site continue sendo referência? Vamos estruturar um acompanhamento estratégico.',
        ],
    ],
];
