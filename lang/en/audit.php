<?php

return [
    'title' => 'Performance Audit Report',
    'generated_at' => 'Generated at',
    'score' => 'Performance Score',
    'out_of' => 'out of 100',
    'core_web_vitals' => 'Core Web Vitals',
    'seo' => 'SEO',
    'accessibility' => 'Accessibility',
    'no_issues' => 'No issues found',
    'screenshot_unavailable' => 'Website preview unavailable - the site may have security restrictions',
    'report' => 'Report',
    'cta_text' => 'Talk to Me',

    'lcp' => 'LCP',
    'lcp_full' => 'Largest Contentful Paint',
    'lcp_desc' => 'Measures loading performance. Should occur within 2.5 seconds of page load.',

    'fcp' => 'FCP',
    'fcp_full' => 'First Contentful Paint',
    'fcp_desc' => 'Marks when the first text or image is painted. Good scores are under 1.8 seconds.',

    'cls' => 'CLS',
    'cls_full' => 'Cumulative Layout Shift',
    'cls_desc' => 'Measures visual stability. Pages should maintain a CLS of 0.1 or less.',

    'good' => 'Good',
    'needs_improvement' => 'Needs Improvement',
    'poor' => 'Poor',
    'excellent' => 'Excellent',
    'average' => 'Average',

    'what_means' => 'What do these metrics mean?',
    'powered_by' => 'Powered by',
    'audit_id' => 'Audit ID',
    'data_from' => 'Data from Google PageSpeed Insights',

    'messages' => [
        'very_poor' => '7 out of 10 visitors abandon your site before seeing your main content. You are losing qualified leads because your site takes too long to show what matters. Every extra second means customers going to competitors',
        'poor' => 'Your main content takes too long to appear, causing 40% more page abandonment. Visitors are clicking the "back" button before knowing your products or services. This is killing conversions',
        'excellent' => 'Excellent! Your main content loads quickly, keeping visitors engaged',
        'lcp_very_poor' => '7 out of 10 visitors abandon your site before seeing your main content. You are losing qualified leads because your site takes too long to show what matters. Every extra second means customers going to competitors',
        'lcp_poor' => 'Your main content takes too long to appear, causing 40% more page abandonment. Visitors are clicking the "back" button before knowing your products or services. This is killing your conversions',
        'lcp_excellent' => 'Excellent! Your main content loads quickly, keeping visitors engaged',
        'fcp_very_poor' => 'First impressions are costing you: visitors see a blank screen for too long and assume your site crashed. You are losing money in sales every month just because nothing appears on screen fast enough',
        'fcp_poor' => 'Your site takes too long to give the first sign of life. 53% of mobile users abandon sites that take more than 3 seconds. You are on the thin line between converting or losing the customer',
        'fcp_excellent' => 'Great! Your site responds quickly, conveying professionalism from the first second',
        'cls_very_poor' => 'Buttons that move = frustrated customers. Your site is "jumping" during loading, making visitors click in the wrong place. This generates distrust and immediate abandonment. Unstable sites convert up to 70% less',
        'cls_poor' => 'Elements moving on screen irritate visitors and hurt the experience. Google penalizes unstable sites in ranking. You are losing positions to competitors with more stable sites',
        'cls_excellent' => 'Perfect! Your site offers a stable and professional visual experience',
    ],

    'closing' => [
        'critical' => [
            'headline' => 'Your site is working against you',
            'body' => 'It\'s not your fault. Most sites start fast and slow down as they grow. The problem is that every lost second represents real opportunities slipping through your fingers. While you read this, visitors have already given up on your site and gone to buy from competitors.',
            'stats' => 'Sites with poor performance like yours convert up to 73% less than optimized sites. If you get 1,000 visitors/month, you\'re literally throwing away hundreds of potential customers.',
            'solution' => 'The good news? Performance isn\'t luck, it\'s technique. And unlike marketing that takes months to show results, performance optimization delivers immediate results.',
            'cta' => 'Want to find out how much money you\'re leaving on the table? Let\'s talk without commitment.',
        ],
        'medium' => [
            'headline' => 'You\'re almost there',
            'body' => 'Your site isn\'t bad, but it\'s in the danger zone. You know that feeling of "could be better"? Your visitors feel it too. And in the digital world, "almost good" means "haven\'t bought yet" or "haven\'t hired yet".',
            'stats' => 'The difference between an average site and a fast site can mean 40% more conversions. It\'s literally the difference between growing or stagnating.',
            'solution' => 'You don\'t need to rebuild everything. With strategic adjustments in the right areas (highlighted in red and orange above), your site leaves average and joins the team of those that really sell.',
            'cta' => 'Let\'s identify the 3 adjustments that will bring the biggest impact to your business?',
        ],
        'good' => [
            'headline' => 'You\'re on the right track',
            'body' => 'Congratulations on having a fast site! You\'re already ahead of 90% of competitors. But in the digital market, standing still is going backwards. Sites and technologies evolve, and what\'s fast today may be slow tomorrow.',
            'stats' => null,
            'solution' => 'Keep monitoring monthly. Small changes (new plugin, more images, more traffic) can crash performance without you noticing.',
            'cta' => 'Want to ensure your site continues to be a reference? Let\'s set up strategic monitoring.',
        ],
    ],
];
