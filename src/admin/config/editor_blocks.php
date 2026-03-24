<?php

/**
 * editor_blocks.php
 *
 * Define structure and defaults for editor blocks.
 */
if (!defined('GRINDS_APP')) exit;

$config = [
  // Define quick access blocks
  'quick_blocks' => ['paragraph', 'header', 'image', 'conversation', 'proscons', 'list', 'section', 'columns'],

  // Define block library
  'library' => [
    'basic' => [
      'label' => _t('cat_basic'),
      'items' => [
        'paragraph' => [
          'label' => _t('blk_text'),
          'icon'  => 'outline-document-text',
          'desc'  => _t('desc_text'),
          'default' => ['text' => '']
        ],
        'header' => [
          'label' => _t('blk_header'),
          'icon'  => 'outline-h1',
          'desc'  => _t('desc_header'),
          'default' => ['text' => '', 'level' => 'h2']
        ],
        'list' => [
          'label' => _t('blk_list'),
          'icon'  => 'outline-list-bullet',
          'desc'  => _t('desc_list'),
          'default' => ['style' => 'unordered', 'items' => ['']]
        ],
        'quote' => [
          'label' => _t('blk_quote'),
          'icon'  => 'outline-chat-bubble-left-right',
          'desc'  => _t('desc_quote'),
          'default' => ['text' => '', 'cite' => '', 'citeUrl' => '']
        ],
        'table' => [
          'label' => _t('blk_table'),
          'icon'  => 'outline-table-cells',
          'desc'  => _t('desc_table'),
          'default' => [
            'withHeadings' => true,
            'content' => [['Header 1', 'Header 2'], ['Cell 1', 'Cell 2']]
          ]
        ],
        'code' => [
          'label' => _t('blk_code'),
          'icon'  => 'outline-code-bracket',
          'desc'  => _t('desc_code'),
          'default' => ['language' => 'plaintext', 'code' => '']
        ],
      ]
    ],
    'layout' => [
      'label' => _t('cat_layout'),
      'items' => [
        'section' => [
          'label' => _t('blk_section'),
          'icon'  => 'outline-stop',
          'desc'  => _t('desc_section'),
          'default' => ['text' => '', 'bgColor' => 'gray', 'name' => ''],
          'colors' => [
            'gray'   => ['label' => _t('color_gray'),   'class' => 'bg-theme-bg border-theme-border text-theme-text', 'style' => ''],
            'blue'   => ['label' => _t('color_blue'),   'class' => '', 'style' => 'background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.2); color: #3b82f6;'],
            'yellow' => ['label' => _t('color_yellow'), 'class' => '', 'style' => 'background-color: rgba(234, 179, 8, 0.1); border-color: rgba(234, 179, 8, 0.2); color: #eab308;'],
            'red'    => ['label' => _t('color_red'),    'class' => '', 'style' => 'background-color: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); color: #ef4444;'],
            'green'  => ['label' => _t('color_green'),  'class' => '', 'style' => 'background-color: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); color: #22c55e;'],
          ]
        ],
        'columns' => [
          'label' => _t('blk_columns'),
          'icon'  => 'outline-view-columns',
          'desc'  => _t('desc_columns'),
          'default' => ['leftText' => '', 'rightText' => '', 'ratio' => '1-1']
        ],
        'spacer' => [
          'label' => _t('blk_spacer'),
          'icon'  => 'outline-arrows-up-down',
          'desc'  => _t('desc_spacer'),
          'default' => ['height' => 50]
        ],
        'divider' => [
          'label' => _t('blk_divider'),
          'icon'  => 'outline-minus',
          'desc'  => _t('desc_divider'),
          'default' => []
        ],
        'password_protect' => [
          'label' => _t('blk_password_protect'),
          'icon'  => 'outline-lock-closed',
          'desc'  => _t('desc_password_protect'),
          'default' => ['password' => '', 'message' => '']
        ],
        'tabs' => [
          'label' => _t('blk_tabs'),
          'icon'  => 'outline-folder',
          'desc'  => _t('desc_tabs'),
          'default' => ['items' => [['title' => 'Tab 1', 'content' => '']]]
        ],
        'accordion' => [
          'label' => _t('blk_accordion'),
          'icon'  => 'outline-chevron-down',
          'desc'  => _t('desc_accordion'),
          'default' => ['items' => [['title' => '', 'content' => '']]]
        ],
        'toc' => [
          'label' => _t('blk_toc'),
          'icon'  => 'outline-list-bullet',
          'desc'  => _t('desc_toc'),
          'default' => ['title' => _t('toc_title')]
        ],
      ]
    ],
    'marketing' => [
      'label' => _t('cat_marketing'),
      'items' => [
        'internal_card' => [
          'label' => _t('blk_internal_card'),
          'icon'  => 'outline-link',
          'desc'  => _t('desc_internal_card'),
          'default' => ['id' => '']
        ],
        'search_box' => [
          'label' => _t('blk_search_box'),
          'icon'  => 'outline-magnifying-glass',
          'desc'  => _t('desc_search_box'),
          'default' => ['placeholder' => '']
        ],
        'proscons' => [
          'label' => _t('blk_proscons'),
          'icon'  => 'outline-scale',
          'desc'  => _t('desc_proscons'),
          'styles' => [
            'pros' => ['class' => 'bg-green-500/10 border-green-500/30 text-green-600'],
            'cons' => ['class' => 'bg-red-500/10 border-red-500/30 text-red-600'],
          ],
          'default' => [
            'pros_title' => _t('lbl_pros_title'),
            'pros_items' => [''],
            'cons_title' => _t('lbl_cons_title'),
            'cons_items' => ['']
          ]
        ],
        'rating' => [
          'label' => _t('blk_rating'),
          'icon'  => 'outline-star',
          'desc'  => _t('desc_rating'),
          'default' => ['score' => 5, 'max' => 5, 'color' => 'gold'],
          'colors' => [
            'gold'  => ['label' => _t('color_yellow'),  'class' => '', 'style' => 'color: #eab308;'],
            'red'   => ['label' => _t('color_red'),   'class' => '', 'style' => 'color: #ef4444;'],
            'blue'  => ['label' => _t('color_blue'),  'class' => '', 'style' => 'color: #3b82f6;'],
            'green' => ['label' => _t('color_green'), 'class' => '', 'style' => 'color: #22c55e;'],
          ]
        ],
        'qrcode' => [
          'label' => _t('blk_qrcode'),
          'icon'  => 'outline-qr-code',
          'desc'  => _t('desc_qrcode'),
          'default' => ['url' => '', 'size' => 150]
        ],
        'post_grid' => [
          'label' => _t('blk_post_grid'),
          'icon'  => 'outline-numbered-list',
          'desc'  => _t('desc_post_grid'),
          'default' => ['limit' => 6, 'columns' => '3', 'category' => '', 'style' => 'card']
        ],
        'social_share' => [
          'label' => _t('blk_social_share'),
          'icon'  => 'outline-share',
          'desc'  => _t('desc_social_share'),
          'default' => ['align' => 'center', 'text' => '']
        ],
        'author' => [
          'label' => _t('blk_author'),
          'icon'  => 'outline-user-circle',
          'desc'  => _t('desc_author'),
          'default' => ['type' => 'Person', 'name' => '', 'role' => '', 'bio' => '', 'image' => '', 'link' => ''],
          'types' => [
            'Person' => ['label' => '個人 (Person)'],
            'Organization' => ['label' => '組織・会社 (Organization)']
          ]
        ],
      ]
    ],
    'media' => [
      'label' => _t('cat_media'),
      'items' => [
        'image' => [
          'label' => _t('blk_image'),
          'icon'  => 'outline-photo',
          'desc'  => _t('desc_image'),
          'default' => ['url' => '', 'caption' => '', 'alt' => '']
        ],
        'gallery' => [
          'label' => _t('blk_gallery'),
          'icon'  => 'outline-squares-2x2',
          'desc'  => _t('desc_gallery'),
          'default' => ['images' => [], 'columns' => '3']
        ],
        'carousel' => [
          'label' => _t('blk_carousel'),
          'icon'  => 'outline-film',
          'desc'  => _t('desc_carousel'),
          'default' => ['images' => [], 'autoplay' => true]
        ],
        'video' => [
          'label' => _t('blk_video'),
          'icon'  => 'outline-film',
          'desc'  => _t('desc_video'),
          'default' => ['url' => '', 'autoplay' => false, 'loop' => false, 'muted' => false]
        ],
        'audio' => [
          'label' => _t('blk_audio'),
          'icon'  => 'outline-musical-note',
          'desc'  => _t('desc_audio'),
          'default' => ['title' => '', 'url' => '']
        ],
        'before_after' => [
          'label' => _t('blk_before_after'),
          'icon'  => 'outline-view-columns',
          'desc'  => _t('desc_before_after'),
          'default' => ['beforeUrl' => '', 'afterUrl' => '', 'beforeLabel' => 'Before', 'afterLabel' => 'After']
        ],
        'download' => [
          'label' => _t('blk_download'),
          'icon'  => 'outline-arrow-down-tray',
          'desc'  => _t('desc_download'),
          'default' => ['title' => _t('btn_download'), 'url' => '', 'fileSize' => '']
        ],
        'pdf' => [
          'label' => _t('blk_pdf'),
          'icon'  => 'outline-document',
          'desc'  => _t('desc_pdf'),
          'default' => ['title' => '', 'url' => '']
        ],
      ]
    ],
    'design' => [
      'label' => _t('cat_design'),
      'items' => [
        'button' => [
          'label' => _t('blk_button'),
          'icon'  => 'outline-cursor-arrow-rays',
          'desc'  => _t('desc_button'),
          'default' => ['text' => '', 'url' => '', 'color' => 'primary', 'external' => true],
          'colors' => [
            'primary' => ['label' => _t('btn_color_main'),  'class' => 'bg-theme-primary text-theme-on-primary border-transparent', 'style' => ''],
            'success' => ['label' => _t('btn_color_green'), 'class' => '', 'style' => 'background-color: #22c55e; color: #ffffff; border-color: transparent;'],
            'danger'  => ['label' => _t('btn_color_red'),   'class' => '', 'style' => 'background-color: #ef4444; color: #ffffff; border-color: transparent;'],
            'dark'    => ['label' => _t('btn_color_black'), 'class' => '', 'style' => 'background-color: #1f2937; color: #ffffff; border-color: transparent;'],
          ]
        ],
        'card' => [
          'label' => _t('blk_card'),
          'icon'  => 'outline-link',
          'desc'  => _t('desc_card'),
          'default' => ['url' => '', 'title' => '', 'description' => '', 'image' => '', 'align' => 'center']
        ],
        'conversation' => [
          'label' => _t('blk_conversation'),
          'icon'  => 'outline-chat-bubble-left-right',
          'desc'  => _t('desc_conversation'),
          'default' => ['name' => _t('ph_name'), 'text' => '', 'image' => '', 'position' => 'left'],
          'styles' => [
            'left' => ['class' => ''],
            'right' => ['class' => 'bg-green-500/10 border-green-500/20'],
          ]
        ],
        'callout' => [
          'label' => _t('blk_callout'),
          'icon'  => 'outline-information-circle',
          'desc'  => _t('desc_callout'),
          'default' => ['text' => '', 'style' => 'info'],
          'styles' => [
            'info'    => ['label' => _t('style_info'), 'class' => 'border-blue-500 bg-blue-500/10 text-blue-600'],
            'warning' => ['label' => _t('style_warn'), 'class' => 'border-yellow-500 bg-yellow-500/10 text-yellow-600'],
            'success' => ['label' => _t('style_check'), 'class' => 'border-green-500 bg-green-500/10 text-green-600'],
            'danger'  => ['label' => _t('style_alert'), 'class' => 'border-red-500 bg-red-500/10 text-red-600'],
          ]
        ],
        'icon_list' => [
          'label' => _t('blk_icon_list'),
          'icon'  => 'outline-list-bullet',
          'desc'  => _t('desc_icon_list'),
          'default' => ['icon' => 'check', 'color' => 'green', 'items' => ['']],
          'icons' => [
            'check' => ['label' => _t('opt_icon_check'), 'svg' => 'outline-check'],
            'check_circle' => ['label' => _t('opt_icon_check_circle'), 'svg' => 'outline-check-circle'],
            'star' => ['label' => _t('opt_icon_star'), 'svg' => 'outline-star'],
            'arrow' => ['label' => _t('opt_icon_arrow'), 'svg' => 'outline-chevron-right'],
            'cross' => ['label' => _t('opt_icon_cross'), 'svg' => 'outline-x-mark'],
            'info' => ['label' => _t('opt_icon_info'), 'svg' => 'outline-information-circle'],
          ],
          'colors' => [
            'green'  => ['label' => _t('color_green'), 'class' => 'text-green-500'],
            'blue'   => ['label' => _t('color_blue'), 'class' => 'text-blue-500'],
            'red'    => ['label' => _t('color_red'), 'class' => 'text-red-500'],
            'yellow' => ['label' => _t('color_yellow'), 'class' => 'text-yellow-500'],
            'gray'   => ['label' => _t('color_gray'), 'class' => 'text-gray-500'],
            'primary' => ['label' => _t('btn_color_main'), 'class' => 'text-theme-primary'],
          ]
        ],
        'timeline' => [
          'label' => _t('blk_timeline'),
          'icon'  => 'outline-clock',
          'desc'  => _t('desc_timeline'),
          'default' => ['items' => [['date' => '', 'title' => '', 'content' => '']]],
          'styles' => [
            'dot' => ['class' => 'bg-blue-600'],
          ]
        ],
        'step' => [
          'label' => _t('blk_step'),
          'icon'  => 'outline-list-bullet',
          'desc'  => _t('desc_step'),
          'default' => ['items' => [['title' => _t('def_step') . ' 1', 'desc' => '']]],
          'styles' => [
            'dot' => ['class' => 'bg-blue-600 text-white'],
          ]
        ],
        'price' => [
          'label' => _t('blk_price'),
          'icon'  => 'outline-currency-dollar',
          'desc'  => _t('desc_price'),
          'default' => ['items' => [['plan' => _t('def_plan_basic'), 'price' => _t('def_price_zero'), 'features' => '', 'recommend' => false]]],
          'styles' => [
            'normal' => ['class' => 'border-theme-border relative group'],
            'recommend' => ['class' => 'border-blue-500 ring-1 ring-blue-500 shadow-sm'],
            'recommend_text' => ['class' => 'text-blue-600'],
          ]
        ],
        'testimonial' => [
          'label' => _t('blk_testimonial'),
          'icon'  => 'outline-chat-bubble-bottom-center-text',
          'desc'  => _t('desc_testimonial'),
          'default' => ['name' => '', 'role' => '', 'comment' => '', 'image' => '']
        ],
        'progress_bar' => [
          'label' => _t('blk_progress_bar'),
          'icon'  => 'outline-chart-bar',
          'desc'  => _t('desc_progress_bar'),
          'default' => ['items' => [['label' => 'Skill', 'percentage' => 80, 'color' => 'primary']]],
          'colors' => [
            'primary' => ['label' => _t('btn_color_main'),  'class' => 'bg-theme-primary'],
            'success' => ['label' => _t('color_green'), 'class' => 'bg-green-500'],
            'warning' => ['label' => _t('color_yellow'), 'class' => 'bg-yellow-500'],
            'danger'  => ['label' => _t('color_red'),   'class' => 'bg-red-500'],
            'dark'    => ['label' => _t('btn_color_black'), 'class' => 'bg-gray-800'],
          ]
        ],
      ]
    ],
    'embed' => [
      'label' => _t('cat_embed'),
      'items' => [
        'map' => [
          'label' => _t('blk_map'),
          'icon'  => 'outline-map',
          'desc'  => _t('desc_map'),
          'default' => ['code' => '']
        ],
        'embed' => [
          'label' => _t('blk_embed'),
          'icon'  => 'outline-play-circle',
          'desc'  => _t('desc_embed'),
          'default' => ['url' => '', 'align' => 'center']
        ],
        'html' => [
          'label' => _t('blk_html'),
          'icon'  => 'outline-code-bracket-square',
          'desc'  => _t('desc_html'),
          'default' => ['code' => '']
        ],
        'countdown' => [
          'label' => _t('blk_countdown'),
          'icon'  => 'outline-clock',
          'desc'  => _t('desc_countdown'),
          'default' => ['deadline' => '', 'message' => _t('ph_countdown_msg')]
        ],
        'math' => [
          'label' => _t('blk_math'),
          'icon'  => 'outline-beaker',
          'desc'  => _t('desc_math'),
          'default' => ['code' => '', 'display' => 'block']
        ],
      ]
    ]
  ]
];

return function_exists('apply_filters') ? apply_filters('grinds_editor_blocks', $config) : $config;
