<?php

return [
    'title' => 'Performance Audit Report',
    'generated_at' => 'Generated at',
    'score' => 'Performance Score',
    'out_of' => 'out of 100',
    'core_web_vitals' => 'Core Web Vitals',

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
];
