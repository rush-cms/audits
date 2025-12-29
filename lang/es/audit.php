<?php

return [
    'title' => 'Informe de Auditoría de Rendimiento',
    'generated_at' => 'Generado el',
    'score' => 'Puntuación de Rendimiento',
    'out_of' => 'de 100',
    'core_web_vitals' => 'Core Web Vitals',
    'seo' => 'SEO',
    'accessibility' => 'Accesibilidad',
    'no_issues' => 'No se encontraron problemas',
    'screenshot_unavailable' => 'Vista previa del sitio no disponible - el sitio puede tener restricciones de seguridad',
    'report' => 'Informe',
    'cta_text' => 'Habla Conmigo',

    'lcp' => 'LCP',
    'lcp_full' => 'Largest Contentful Paint',
    'lcp_desc' => 'Mide el rendimiento de carga. Debe ocurrir en menos de 2.5 segundos.',

    'fcp' => 'FCP',
    'fcp_full' => 'First Contentful Paint',
    'fcp_desc' => 'Marca cuando se renderiza el primer texto o imagen. Buenos valores son menores a 1.8 segundos.',

    'cls' => 'CLS',
    'cls_full' => 'Cumulative Layout Shift',
    'cls_desc' => 'Mide la estabilidad visual. Las páginas deben mantener un CLS de 0.1 o menos.',

    'good' => 'Bueno',
    'needs_improvement' => 'Necesita Mejorar',
    'poor' => 'Malo',
    'excellent' => 'Excelente',
    'average' => 'Promedio',

    'what_means' => '¿Qué significan estas métricas?',
    'powered_by' => 'Desarrollado por',
    'audit_id' => 'ID de Auditoría',
    'data_from' => 'Datos de Google PageSpeed Insights',

    'messages' => [
        'very_poor' => '7 de cada 10 visitantes abandonan tu sitio antes de ver tu contenido principal. Estás perdiendo leads calificados porque tu sitio tarda demasiado en mostrar lo que importa. Cada segundo extra significa clientes yendo a la competencia',
        'poor' => 'Tu contenido principal tarda demasiado en aparecer, causando 40% más abandono de página. Los visitantes hacen clic en el botón "atrás" antes de conocer tus productos o servicios. Esto está matando las conversiones',
        'excellent' => '¡Excelente! Tu contenido principal carga rápidamente, manteniendo a los visitantes comprometidos',
        'lcp_very_poor' => '7 de cada 10 visitantes abandonan tu sitio antes de ver tu contenido principal. Estás perdiendo leads calificados porque tu sitio tarda demasiado en mostrar lo que importa. Cada segundo extra significa clientes yendo a la competencia',
        'lcp_poor' => 'Tu contenido principal tarda demasiado en aparecer, causando 40% más abandono de página. Los visitantes hacen clic en el botón "atrás" antes de conocer tus productos o servicios. Esto está matando tus conversiones',
        'lcp_excellent' => '¡Excelente! Tu contenido principal carga rápidamente, manteniendo a los visitantes comprometidos',
        'fcp_very_poor' => 'Las primeras impresiones te están costando caro: los visitantes ven una pantalla en blanco demasiado tiempo y asumen que tu sitio se bloqueó. Estás perdiendo dinero en ventas cada mes solo porque nada aparece en pantalla lo suficientemente rápido',
        'fcp_poor' => 'Tu sitio tarda demasiado en dar la primera señal de vida. El 53% de los usuarios móviles abandonan sitios que tardan más de 3 segundos. Estás en la línea delgada entre convertir o perder al cliente',
        'fcp_excellent' => '¡Genial! Tu sitio responde rápidamente, transmitiendo profesionalismo desde el primer segundo',
        'cls_very_poor' => 'Botones que se mueven = clientes frustrados. Tu sitio está "saltando" durante la carga, haciendo que los visitantes hagan clic en el lugar equivocado. Esto genera desconfianza y abandono inmediato. Los sitios inestables convierten hasta 70% menos',
        'cls_poor' => 'Elementos moviéndose en la pantalla irritan a los visitantes y perjudican la experiencia. Google penaliza sitios inestables en el ranking. Estás perdiendo posiciones ante competidores con sitios más estables',
        'cls_excellent' => '¡Perfecto! Tu sitio ofrece una experiencia visual estable y profesional',
    ],

    'closing' => [
        'critical' => [
            'headline' => 'Tu sitio está trabajando en tu contra',
            'body' => 'No es tu culpa. La mayoría de los sitios nacen rápidos y se vuelven lentos conforme crecen. El problema es que cada segundo perdido representa oportunidades reales escapándose entre los dedos. Mientras lees esto, visitantes ya abandonaron tu sitio y fueron a comprar de la competencia.',
            'stats' => 'Sitios con performance mala como la actual convierten hasta 73% menos que sitios optimizados. Si recibes 1.000 visitantes/mes, estás literalmente tirando a la basura cientos de potenciales clientes.',
            'solution' => '¿La buena noticia? Performance no es suerte, es técnica. Y diferente del marketing que demora meses para dar resultado, optimización de performance entrega resultados inmediatos.',
            'cta' => '¿Quieres descubrir cuánto dinero estás dejando en la mesa? Hablemos sin compromiso.',
        ],
        'medium' => [
            'headline' => 'Estás casi ahí',
            'body' => 'Tu sitio no está malo, pero está en la zona de peligro. ¿Conoces esa sensación de "podría estar mejor"? Tus visitantes también la sienten. Y en el mundo digital, "casi bueno" significa "aún no compré" o "aún no contraté".',
            'stats' => 'La diferencia entre un sitio mediano y un sitio rápido puede significar 40% más conversiones. Es literalmente la diferencia entre crecer o estancarse.',
            'solution' => 'No necesitas rehacer todo. Con ajustes estratégicos en las áreas correctas (destacadas en rojo y naranja arriba), tu sitio sale del promedio y entra al equipo de los que realmente venden.',
            'cta' => '¿Vamos a identificar los 3 ajustes que traerán el mayor impacto a tu negocio?',
        ],
        'good' => [
            'headline' => 'Vas por el camino correcto',
            'body' => '¡Felicitaciones por tener un sitio rápido! Ya estás adelante del 90% de los competidores. Pero en el mercado digital, quedarse parado es retroceder. Sitios y tecnologías evolucionan, y lo que es rápido hoy puede ser lento mañana.',
            'stats' => null,
            'solution' => 'Continúa monitoreando mensualmente. Pequeños cambios (nuevo plugin, más imágenes, más tráfico) pueden derribar la performance sin que lo notes.',
            'cta' => '¿Quieres garantizar que tu sitio continúe siendo referencia? Vamos a estructurar un acompañamiento estratégico.',
        ],
    ],
];
