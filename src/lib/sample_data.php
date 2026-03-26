<?php

/**
 * Define default content and installation logic.
 */
if (!defined('GRINDS_APP')) exit;

/**
 * Retrieve default settings.
 */
function Grinds_GetDefaultSettings($lang = 'en', $overrides = [])
{
    // Load options functions
    if (!function_exists('grinds_get_default_settings')) {
        require_once __DIR__ . '/functions/options.php';
    }

    // Get base defaults
    $defaults = grinds_get_default_settings($lang);

    return array_merge($defaults, $overrides);
}

/**
 * Retrieve sample data.
 */
function Grinds_GetSampleData($lang = 'en')
{
    $isJa = ($lang === 'ja');
    $now = date('Y-m-d H:i:s');

    // Define placeholder image
    $dummyImg = _grinds_ensure_sample_image('sample_image.svg', 'Sample Image', '#2d2d2d', '#eeeeee');

    return [
        // Define tags
        'tags' => [
            ['name' => $isJa ? 'おすすめ' : 'Featured', 'slug' => 'featured'],
            ['name' => $isJa ? 'チュートリアル' : 'Tutorial', 'slug' => 'tutorial'],
            ['name' => $isJa ? 'アップデート' : 'Update', 'slug' => 'update'],
            ['name' => $isJa ? '開発' : 'Dev', 'slug' => 'dev'],
        ],

        // Define pages
        'pages' => [
            // Define home page
            [
                'title' => 'Home',
                'slug'  => 'home',
                'hero_image' => '',
                'hero_settings' => [
                    'title' => $isJa ? 'ビジネスを加速させる次世代CMS' : 'Accelerate Your Business',
                    'subtext' => $isJa ? 'GrindSiteは、直感的な操作と高いパフォーマンスを兼ね備えた、\nモダンなウェブサイト構築プラットフォームです。' : 'GrindSite is a modern website building platform\ncombining intuitive operation with high performance.',
                    'layout' => 'standard',
                    'buttons' => [
                        ['text' => $isJa ? '無料で始める' : 'Get Started Free', 'url' => '#', 'style' => 'primary'],
                        ['text' => $isJa ? 'デモを見る' : 'View Demo', 'url' => '#', 'style' => 'white']
                    ]
                ],
                'content' => [
                    'blocks' => [
                        // Define features
                        ['type' => 'header', 'data' => ['text' => $isJa ? '選ばれる理由' : 'Why Choose Us', 'level' => 'h2']],
                        ['type' => 'columns', 'data' => [
                            'ratio' => '1-1',
                            'leftText' => $isJa
                                ? "<b>🚀 超高速パフォーマンス</b><br>データベース不要のフラットファイル構造により、驚異的な読み込み速度を実現。SEOにも有利です。"
                                : "<b>🚀 Blazing Fast</b><br>Flat-file structure without database requirements ensures incredible loading speeds. Great for SEO.",
                            'rightText' => $isJa
                                ? "<b>🎨 直感的なブロックエディタ</b><br>専門知識がなくても、積み木のようにブロックを組み合わせるだけで美しいページが作れます。"
                                : "<b>🎨 Intuitive Block Editor</b><br>Create beautiful pages just by assembling blocks like building bricks, no coding required."
                        ]],
                        // Define testimonials
                        ['type' => 'header', 'data' => ['text' => $isJa ? 'お客様の声' : 'Testimonials', 'level' => 'h2']],
                        ['type' => 'testimonial', 'data' => [
                            'name' => 'Sarah Connor',
                            'role' => 'CTO, TechCorp',
                            'comment' => $isJa ? '導入して1ヶ月でサイトの更新頻度が3倍になりました。もう以前のCMSには戻れません。' : 'Within a month of implementation, our site update frequency tripled. I can\'t go back to the old CMS.',
                            'image' => ''
                        ]],
                        // Define gallery
                        ['type' => 'header', 'data' => ['text' => $isJa ? '機能ギャラリー' : 'Feature Gallery', 'level' => 'h2']],
                        ['type' => 'gallery', 'data' => [
                            'columns' => '3',
                            'images' => [
                                ['url' => $dummyImg, 'caption' => $isJa ? 'ダッシュボード' : 'Dashboard'],
                                ['url' => $dummyImg, 'caption' => $isJa ? 'エディタ' : 'Editor'],
                                ['url' => $dummyImg, 'caption' => $isJa ? 'モバイル表示' : 'Mobile View']
                            ]
                        ]],
                        // Define CTA
                        ['type' => 'section', 'data' => [
                            'bgColor' => 'blue',
                            'text' => $isJa
                                ? "<div class='text-center'><b>準備はいいですか？</b><br>今すぐGrindSiteで新しいウェブサイト体験を始めましょう。</div>"
                                : "<div class='text-center'><b>Ready to start?</b><br>Start your new website experience with GrindSite today.</div>"
                        ]],
                        ['type' => 'button', 'data' => ['text' => $isJa ? '今すぐダウンロード' : 'Download Now', 'url' => '#', 'color' => 'primary']]
                    ]
                ]
            ],

            // Define about page
            [
                'title' => $isJa ? '会社概要' : 'About Us',
                'slug'  => 'about',
                'content' => [
                    'blocks' => [
                        ['type' => 'header', 'data' => ['text' => $isJa ? '私たちのミッション' : 'Our Mission', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? '私たちは「テクノロジーで世界をシンプルに」をミッションに掲げ、複雑なWeb制作の課題に取り組んでいます。'
                            : 'Our mission is "Simplify the world with technology". We tackle the challenges of complex web development.']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? '沿革' : 'History', 'level' => 'h2']],
                        ['type' => 'timeline', 'data' => [
                            'items' => [
                                ['date' => '2023', 'title' => $isJa ? '設立' : 'Founded', 'content' => $isJa ? '東京にて株式会社設立' : 'Established in Tokyo.'],
                                ['date' => '2024', 'title' => $isJa ? 'グローバル展開' : 'Global Launch', 'content' => $isJa ? 'GrindSite バージョン1.0を全世界同時リリース' : 'Released GrindSite v1.0 worldwide.'],
                                ['date' => '2025', 'title' => $isJa ? '拡大期' : 'Expansion', 'content' => $isJa ? '導入企業数が1,000社を突破' : 'Surpassed 1,000 corporate users.']
                            ]
                        ]],

                        ['type' => 'header', 'data' => ['text' => $isJa ? '会社情報' : 'Company Info', 'level' => 'h2']],
                        ['type' => 'table', 'data' => [
                            'withHeadings' => false,
                            'content' => [
                                [$isJa ? '会社名' : 'Company Name', 'GrindSite Inc.'],
                                [$isJa ? '代表者' : 'CEO', 'Koji Udagawa'],
                                [$isJa ? '所在地' : 'Address', $isJa ? '東京都渋谷区...' : 'Shibuya, Tokyo, Japan'],
                                [$isJa ? '設立' : 'Established', '2023-01-01'],
                            ]
                        ]],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'アクセス' : 'Access', 'level' => 'h2']],
                        ['type' => 'map', 'data' => ['code' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3241.747975472345!2d139.7016358!3d35.6585805!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x60188b563b00109f%3A0x337328def1e2ab26!2sShibuya%20Station!5e0!3m2!1sen!2sjp!4v1600000000000!5m2!1sen!2sjp" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>']]
                    ]
                ]
            ],

            // Define services page
            [
                'title' => $isJa ? 'サービス・料金' : 'Services & Pricing',
                'slug'  => 'services',
                'content' => [
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? 'お客様のニーズに合わせた柔軟なプランをご用意しています。' : 'We offer flexible plans tailored to your needs.']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? '導入の流れ' : 'Process', 'level' => 'h2']],
                        ['type' => 'step', 'data' => [
                            'items' => [
                                ['title' => $isJa ? 'お問い合わせ' : 'Contact', 'desc' => $isJa ? 'フォームよりご連絡ください。' : 'Contact us via the form.'],
                                ['title' => $isJa ? 'ヒアリング' : 'Consulting', 'desc' => $isJa ? '担当者が要件をお伺いします。' : 'We will discuss your requirements.'],
                                ['title' => $isJa ? '導入・開発' : 'Development', 'desc' => $isJa ? '最短1週間で導入可能です。' : 'Implementation in as little as 1 week.']
                            ]
                        ]],

                        ['type' => 'header', 'data' => ['text' => $isJa ? '料金プラン' : 'Pricing', 'level' => 'h2']],
                        ['type' => 'price', 'data' => [
                            'items' => [
                                ['plan' => $isJa ? 'スターター' : 'Starter', 'price' => 'Free', 'features' => $isJa ? "基本機能\n1ユーザー\nコミュニティサポート" : "Basic Features\n1 User\nCommunity Support", 'recommend' => false],
                                ['plan' => $isJa ? 'プロ' : 'Pro', 'price' => '$29', 'features' => $isJa ? "全機能利用可\n無制限ユーザー\n優先メールサポート" : "All Features\nUnlimited Users\nPriority Support", 'recommend' => true],
                                ['plan' => $isJa ? 'エンタープライズ' : 'Enterprise', 'price' => 'Custom', 'features' => $isJa ? "専任サポート\nSLA保証\nオンプレミス対応" : "Dedicated Support\nSLA\nOn-premise", 'recommend' => false],
                            ]
                        ]],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'よくある質問' : 'FAQ', 'level' => 'h2']],
                        ['type' => 'accordion', 'data' => [
                            'items' => [
                                ['title' => $isJa ? '支払い方法は？' : 'Payment methods?', 'content' => $isJa ? 'クレジットカード、銀行振込に対応しています。' : 'We accept Credit Cards and Bank Transfer.'],
                                ['title' => $isJa ? '解約はいつでも可能ですか？' : 'Can I cancel anytime?', 'content' => $isJa ? 'はい、月契約であればいつでも解約可能です。' : 'Yes, you can cancel monthly subscriptions at any time.']
                            ]
                        ]]
                    ]
                ]
            ],

            // Define contact page
            [
                'title' => $isJa ? 'お問い合わせ' : 'Contact Us',
                'slug'  => 'contact',
                'content' => [
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? 'ご質問やご相談は、以下のフォームよりお気軽にお問い合わせください。' : 'Please feel free to contact us using the form below.']],
                        ['type' => 'callout', 'data' => ['style' => 'info', 'text' => $isJa ? '通常、24時間以内に返信いたします。' : 'We usually reply within 24 hours.']],
                    ]
                ]
            ]
        ],

        // Define posts
        'posts' => [
            // Define welcome post
            [
                'title' => $isJa ? 'GrindSiteへようこそ！' : 'Welcome to GrindSite!',
                'slug'  => 'welcome',
                'cat_slug' => 'news',
                'tags' => ['featured', 'update'],
                'description' => $isJa ? 'GrindSiteのインストールありがとうございます。最初のステップをご案内します。' : 'Thank you for installing GrindSite. Here are the first steps.',
                'hero_image' => '',
                'show_toc' => 0,
                'content' => [
                    'blocks' => [
                        ['type' => 'image', 'data' => ['url' => $dummyImg, 'caption' => $isJa ? 'GrindSite 管理画面' : 'GrindSite Admin Panel']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? 'GrindSiteをインストールしていただきありがとうございます。このシステムは、あなたのコンテンツ発信を強力にサポートします。<br><br>データベース設定不要で、サーバーに置くだけで動作する手軽さと、本格的なCMS機能を両立しました。'
                            : 'Thank you for installing GrindSite. This system powerfully supports your content creation.<br><br>It combines the ease of a flat-file system with full-fledged CMS features.']],
                        ['type' => 'header', 'data' => ['text' => $isJa ? '次のステップ' : 'Next Steps', 'level' => 'h3']],
                        ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => [
                            $isJa ? '管理画面の「設定」からサイト情報を編集' : 'Edit site info from Settings',
                            $isJa ? '「外観」でテーマやスキンを変更' : 'Change themes and skins',
                            $isJa ? '新しい記事を投稿してみる' : 'Try creating a new post'
                        ]]],
                        ['type' => 'button', 'data' => ['text' => $isJa ? 'ドキュメントを見る' : 'Read Documentation', 'url' => '#', 'color' => 'primary']]
                    ]
                ]
            ],
            // Define advanced demo
            [
                'title' => $isJa ? '高度な機能のデモ: 目次とヒーローヘッダー' : 'Advanced Features: TOC & Hero Header',
                'slug'  => 'advanced-features',
                'cat_slug' => 'tutorials',
                'tags' => ['tutorial', 'featured'],
                'description' => $isJa ? '目次自動生成機能やヒーローヘッダーの設定例です。' : 'Demo of Table of Contents and Hero Header settings.',
                'hero_image' => $dummyImg,
                'hero_settings' => [
                    'title' => $isJa ? '高度なカスタマイズ' : 'Advanced Customization',
                    'subtext' => $isJa ? '記事ごとに個別のヘッダー画像とタイトルを設定できます。' : 'Set individual header images and titles for each post.',
                    'layout' => 'standard',
                    'overlay' => true
                ],
                'show_toc' => 1,
                'content' => [
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? 'この記事では、<b>目次（TOC）の自動生成</b>と<b>ヒーローヘッダー</b>の表示をデモしています。右側（モバイルでは上部）に目次が表示されているはずです。'
                            : 'This post demonstrates <b>Table of Contents (TOC)</b> generation and <b>Hero Header</b> display. You should see the TOC on the right (or top on mobile).']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'セクション1: ヒーローヘッダー' : 'Section 1: Hero Header', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? '投稿編集画面の「設定」タブから、ヒーロー画像とキャッチコピーを設定できます。これにより、記事のファーストビューを印象的にすることができます。'
                            : 'You can set a hero image and tagline from the "Settings" tab in the post editor. This makes the first view of your article impressive.']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'セクション2: 目次 (TOC)' : 'Section 2: Table of Contents', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? '「目次を表示する」オプションをオンにすると、記事内のH2〜H4タグを自動的に収集して目次を生成します。長文記事に必須の機能です。'
                            : 'Turning on the "Show Table of Contents" option automatically collects H2-H4 tags in the article to generate a TOC. Essential for long articles.']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'サブセクション 2.1' : 'Subsection 2.1', 'level' => 'h3']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? 'サブセクションのコンテンツ...' : 'Content for subsection...']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'セクション3: メタデータ' : 'Section 3: Metadata', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? 'SEO用のメタディスクリプションや、SNSシェアボタンの表示/非表示も記事ごとにコントロール可能です。'
                            : 'You can also control SEO meta descriptions and the visibility of SNS share buttons for each article.']],
                    ]
                ]
            ],
            // Define formatting guide
            [
                'title' => $isJa ? '記事の書き方・スタイルガイド' : 'Formatting Style Guide',
                'slug'  => 'style-guide',
                'cat_slug' => 'tutorials',
                'tags' => ['tutorial'],
                'content' => [
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? 'この記事は、GrindSiteで使用できる様々なブロックの表示サンプルです。' : 'This post demonstrates various blocks available in GrindSite.']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? '見出し (Headings)' : 'Headings', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? 'H2からH6までの見出しを設定できます。SEO構造化に役立ちます。' : 'You can set headings from H2 to H6. Useful for SEO structure.']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'リッチメディア' : 'Rich Media', 'level' => 'h2']],
                        ['type' => 'image', 'data' => ['url' => $dummyImg, 'caption' => $isJa ? '全幅画像サポート' : 'Full width image support']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'コードと引用' : 'Code & Quotes', 'level' => 'h2']],
                        ['type' => 'code', 'data' => ['language' => 'javascript', 'code' => "function hello() {\n  console.log('Hello GrindSite!');\n}"]],
                        ['type' => 'quote', 'data' => ['text' => 'Stay hungry, stay foolish.', 'cite' => 'Steve Jobs']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'インタラクティブ要素' : 'Interactive Elements', 'level' => 'h2']],
                        ['type' => 'callout', 'data' => ['style' => 'warning', 'text' => $isJa ? 'これは警告コールアウトブロックです。' : 'This is a warning callout block.']],
                        ['type' => 'accordion', 'data' => ['items' => [['title' => $isJa ? 'カスタマイズできますか？' : 'Can I customize this?', 'content' => $isJa ? 'はい、テーマですべてカスタマイズ可能です。' : 'Yes, everything is customizable via themes.']]]],
                    ]
                ]
            ],
            // Define release notes
            [
                'title' => $isJa ? 'バージョン1.0 リリースノート' : 'Version 1.0 Release Notes',
                'slug'  => 'release-v1',
                'cat_slug' => 'news',
                'tags' => ['update', 'featured'],
                'content' => [
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? '待望のメジャーバージョン1.0をリリースしました。主な変更点は以下の通りです。' : 'We are excited to announce version 1.0. Here are the highlights:']],
                        ['type' => 'proscons', 'data' => [
                            'pros_title' => $isJa ? '新機能' : 'New Features',
                            'pros_items' => $isJa ? ['ブロックエディタ v2', 'ダークモード管理画面', '多言語サポート'] : ['Block Editor v2', 'Dark Mode Admin', 'Multi-language Support'],
                            'cons_title' => $isJa ? '廃止予定' : 'Deprecated',
                            'cons_items' => $isJa ? ['レガシークラシックエディタ'] : ['Legacy Classic Editor']
                        ]],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? 'アップデートは管理画面からワンクリックで行えます。' : 'You can update with one click from the admin panel.']]
                    ]
                ]
            ],

            // Define developer guide
            [
                'title' => $isJa ? '開発者ガイド: ブロックのHTMLをカスタマイズする方法' : 'Developer Guide: How to Customize Block HTML',
                'slug'  => 'dev-custom-blocks',
                'cat_slug' => 'tutorials',
                'tags' => ['dev', 'tutorial', 'featured'],
                'content' => [
                    'blocks' => [
                        ['type' => 'callout', 'data' => [
                            'style' => 'info',
                            'text' => $isJa
                                ? '<b>プロ向けの機能:</b> GrindSiteは、PHPファイルひとつでブロックのHTML構造を完全に制御できます。Tailwind以外のフレームワーク（Bootstrapなど）を使用する場合に特に便利です。'
                                : '<b>Pro Feature:</b> GrindSite allows full control over block HTML structure via simple PHP files. Especially useful when using frameworks other than Tailwind (e.g., Bootstrap).'
                        ]],
                        ['type' => 'header', 'data' => ['text' => $isJa ? '仕組み' : 'How it works', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? '通常、CMSはデフォルトのHTML（Tailwindクラス付き）を出力します。<br>しかし、テーマフォルダ内に特定のファイルを置くことで、その出力を上書きできます。'
                            : 'By default, the CMS outputs HTML with Tailwind classes.<br>However, you can override this output by placing specific files within your theme folder.']],

                        ['type' => 'code', 'data' => [
                            'language' => 'bash',
                            'code' => "theme/\n  └── {your_theme}/\n      └── blocks/\n          ├── button.php   <-- Overrides 'Button' block\n          ├── card.php     <-- Overrides 'Card' block\n          └── ...          <-- Any block type name"
                        ]],

                        ['type' => 'header', 'data' => ['text' => $isJa ? '実装例 (button.php)' : 'Example Implementation (button.php)', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? '例えば、Bootstrapのクラスを使ったボタンに変更したい場合、`theme/{theme}/blocks/button.php` を作成して以下のように記述します。'
                            : 'For example, to change a button to use Bootstrap classes, create `theme/{theme}/blocks/button.php` and write the following:']],

                        ['type' => 'code', 'data' => [
                            'language' => 'php',
                            'code' => "<?php\n// Available variables: \$data, \$text, \$url, \$color, etc.\n\n\$btnClass = (\$color === 'primary') ? 'btn-primary' : 'btn-secondary';\n?>\n\n<div class=\"text-center my-4\">\n  <a href=\"<?= \$url ?>\" class=\"btn <?= \$btnClass ?> btn-lg shadow\">\n    <?= \$text ?>\n  </a>\n</div>"
                        ]],

                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? 'このように、PHPファイルを作成するだけで、直感的にHTMLをコントロールできます。ロジック（functions.php）を編集する必要はありません。'
                            : 'Just by creating a PHP file, you can intuitively control the HTML. No need to edit complex logic in functions.php.']],
                    ]
                ]
            ],
            // Define developer guide 2
            [
                'title' => $isJa ? '開発者ガイド: シンプルなプラグインの作成' : 'Developer Guide: Creating a Simple Plugin',
                'slug'  => 'dev-simple-plugin',
                'cat_slug' => 'tutorials',
                'tags' => ['dev', 'tutorial'],
                'content' => [
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? 'GrindSiteは軽量なフックシステムを備えており、プラグインで機能を拡張できます。この記事では、簡単なプラグインの作り方を解説します。'
                            : 'GrindSite features a lightweight hook system that allows you to extend functionality via plugins. This article explains how to create a simple plugin.']],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'プラグインの配置' : 'Plugin Placement', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? '`plugins` ディレクトリ内に `.php` ファイルを配置するだけで自動的に認識され、実行されます。'
                            : 'Simply place a `.php` file in the `plugins` directory to be automatically recognized and executed.']],

                        ['type' => 'code', 'data' => [
                            'language' => 'bash',
                            'code' => "plugins/\n  └── my-plugin.php"
                        ]],

                        ['type' => 'header', 'data' => ['text' => $isJa ? 'コード例' : 'Code Example', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa
                            ? '以下は、フッターにカスタムテキストを追加する簡単なプラグインの例です。'
                            : 'Here is an example of a simple plugin that adds custom text to the footer.']],

                        ['type' => 'code', 'data' => [
                            'language' => 'php',
                            'code' => "<?php\n/*\nPlugin Name: My First Plugin\nDescription: Adds a message to the footer.\nVersion: 1.0\n*/\n\nadd_action('grinds_footer', function() {\n    echo '<p style=\"text-align:center\">Powered by My Plugin</p>';\n});"
                        ]],

                        ['type' => 'callout', 'data' => [
                            'style' => 'info',
                            'text' => $isJa
                                ? 'プラグインを一時的に無効化したい場合は、ファイル名の先頭に `_`（アンダースコア）を追加してください。（例: `_my-plugin.php`）'
                                : 'To temporarily disable a plugin, simply add an underscore `_` to the beginning of the filename (e.g., `_my-plugin.php`).'
                        ]],
                    ]
                ]
            ],
            // Define AI-hidden post
            [
                'title' => $isJa ? '【AI非公開】社内向け情報' : '[AI-Hidden] Internal Information',
                'slug'  => 'internal-info',
                'cat_slug' => 'news',
                'tags' => ['dev'],
                'description' => $isJa ? 'このページは llms.txt と llms-full.txt には含まれません。' : 'This page will not be included in llms.txt and llms-full.txt.',
                'is_hide_llms' => 1,
                'content' => [
                    'blocks' => [
                        ['type' => 'callout', 'data' => [
                            'style' => 'warning',
                            'text' => $isJa
                                ? 'このコンテンツは `is_hide_llms` フラグが設定されているため、AIクローラー向けの `/llms.txt` および `/llms-full.txt` から除外されます。'
                                : 'This content has the `is_hide_llms` flag set, so it will be excluded from `/llms.txt` and `/llms-full.txt` for AI crawlers.'
                        ]],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? '社外秘の情報をここに記述します。' : 'Confidential information would be written here.']],
                    ]
                ]
            ]
        ],

        // Define templates
        'templates' => [
            [
                'title' => $isJa ? '製品ランディングページ (LP)' : 'Product Landing Page',
                'content' => [
                    'blocks' => [
                        ['type' => 'header', 'data' => ['text' => $isJa ? '製品名' : 'Product Name', 'level' => 'h2']],
                        ['type' => 'paragraph', 'data' => ['text' => $isJa ? 'キャッチーなタグラインをここに入力します。' : 'Catchy tagline goes here.']],
                        ['type' => 'columns', 'data' => ['ratio' => '1-1', 'leftText' => $isJa ? '<b>機能 A</b><br>詳細テキスト。' : '<b>Feature A</b><br>Detail text here.', 'rightText' => $isJa ? '<b>機能 B</b><br>詳細テキスト。' : '<b>Feature B</b><br>Detail text here.']],
                        ['type' => 'price', 'data' => ['items' => [['plan' => $isJa ? 'スタンダード' : 'Standard', 'price' => '$10'], ['plan' => $isJa ? 'プロ' : 'Pro', 'price' => '$30', 'recommend' => true]]]],
                        ['type' => 'button', 'data' => ['text' => $isJa ? '今すぐ購入' : 'Buy Now', 'color' => 'primary']]
                    ]
                ]
            ],
            [
                'title' => $isJa ? '採用情報ページ' : 'Recruitment Page',
                'content' => [
                    'blocks' => [
                        ['type' => 'header', 'data' => ['text' => $isJa ? '私たちと一緒に働きませんか？' : 'Join Our Team', 'level' => 'h2']],
                        ['type' => 'image', 'data' => ['url' => $dummyImg]],
                        ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => [$isJa ? 'フルリモート可' : 'Remote Work', $isJa ? 'フレックス制' : 'Flex Time']]],
                        ['type' => 'button', 'data' => ['text' => $isJa ? '応募する' : 'Apply Now', 'color' => 'success']]
                    ]
                ]
            ]
        ],

        // Define menus
        'menus' => [
            ['location' => 'header', 'label' => $isJa ? 'ホーム' : 'Home', 'url' => '/', 'sort' => 1],
            ['location' => 'header', 'label' => $isJa ? 'ニュース' : 'News', 'url' => '/category/news', 'sort' => 2],
            ['location' => 'header', 'label' => $isJa ? '特集' : 'Featured', 'url' => '/tag/featured', 'sort' => 3],
            ['location' => 'header', 'label' => $isJa ? 'サービス' : 'Services', 'url' => '/services', 'sort' => 4],
            ['location' => 'header', 'label' => $isJa ? '会社概要' : 'About', 'url' => '/about', 'sort' => 5],
            ['location' => 'header', 'label' => $isJa ? 'お問い合わせ' : 'Contact', 'url' => '/contact', 'sort' => 6],

            // Define footer menu
            ['location' => 'footer', 'label' => $isJa ? 'プライバシーポリシー' : 'Privacy Policy', 'url' => '#', 'sort' => 1],
            ['location' => 'footer', 'label' => $isJa ? '利用規約' : 'Terms', 'url' => '#', 'sort' => 2],
            ['location' => 'footer', 'label' => $isJa ? 'お問い合わせ' : 'Contact', 'url' => '/contact', 'sort' => 3],
        ],

        // Define widgets
        'widgets' => [
            // Define profile
            [
                'type' => 'profile',
                'title' => $isJa ? '運営者' : 'Author',
                'content' => $isJa ? 'GrindSite開発チームです。シンプルで強力なツールを提供します。' : 'We are the GrindSite team. Providing simple and powerful tools.',
                'settings' => json_encode(['name' => 'GrindSite', 'image' => '']),
                'sort_order' => 1
            ],
            // Define search
            [
                'type' => 'search',
                'title' => $isJa ? '検索' : 'Search',
                'content' => '',
                'settings' => '{}',
                'sort_order' => 2
            ],
            // Define categories
            [
                'type' => 'categories',
                'title' => $isJa ? 'カテゴリ' : 'Categories',
                'content' => '',
                'settings' => '{}',
                'sort_order' => 3
            ],
            // Define recent posts
            [
                'type' => 'recent',
                'title' => $isJa ? '最新記事' : 'Recent Posts',
                'content' => '',
                'settings' => json_encode(['limit' => 5]),
                'sort_order' => 4
            ],
            // Define banner
            [
                'type' => 'banner',
                'title' => $isJa ? '広告スペース' : 'Ad Space',
                'content' => '',
                'settings' => json_encode([
                    'title' => $isJa ? '広告スペース' : 'Ad Space',
                    'image' => $dummyImg,
                    'link' => '#',
                    'alt' => 'Ad Space'
                ]),
                'sort_order' => 5
            ]
        ]
    ];
}

/**
 * Install sample data.
 */
function Grinds_InstallSampleData($pdo, $lang = 'en', $siteName = 'GrindSite')
{
    $data = Grinds_GetSampleData($lang);
    $now = date('Y-m-d H:i:s');

    // Insert/Update settings for sample data (e.g. for llms.txt)
    $stmtSetting = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
    $stmtSetting->execute(['official_social_links', "https://x.com/GrindSite\nhttps://github.com/grindworks/grind-site"]);

    // Insert tags
    $stmtTag = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
    $stmtCheckTag = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");

    if (!empty($data['tags'])) {
        foreach ($data['tags'] as $tag) {
            $stmtCheckTag->execute([$tag['slug']]);
            if (!$stmtCheckTag->fetchColumn()) {
                $stmtTag->execute([$tag['name'], $tag['slug']]);
            }
        }
    }

    // Insert pages
    $stmtPage = $pdo->prepare("INSERT INTO posts (title, slug, content, search_text, type, status, show_category, show_date, hero_image, hero_settings, created_at, updated_at) VALUES (?, ?, ?, ?, 'page', 'published', 0, 0, ?, ?, ?, ?)");

    // Prepare statement
    $stmtCheck = $pdo->prepare("SELECT count(*) FROM posts WHERE slug = ?");

    foreach ($data['pages'] as $page) {
        $stmtCheck->execute([$page['slug']]);
        $exists = $stmtCheck->fetchColumn();

        if (!$exists) {
            $jsonContent = json_encode($page['content'], JSON_UNESCAPED_UNICODE);
            $heroImg = $page['hero_image'] ?? '';
            $heroSet = isset($page['hero_settings']) ? json_encode($page['hero_settings'], JSON_UNESCAPED_UNICODE) : '';

            // Generate search text
            $searchText = grinds_generate_search_text($page['title'], '', $jsonContent);

            $stmtPage->execute([
                $page['title'],
                $page['slug'],
                $jsonContent,
                $searchText,
                $heroImg,
                $heroSet,
                $now,
                $now
            ]);
        }
    }

    // Insert posts
    $stmtPost = $pdo->prepare("INSERT INTO posts (title, slug, content, search_text, type, status, category_id, show_category, show_date, hero_image, hero_settings, show_toc, show_share_buttons, description, thumbnail, is_hide_llms, created_at, updated_at, deleted_at) VALUES (?, ?, ?, ?, 'post', 'published', ?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)");

    foreach ($data['posts'] as $post) {
        $stmtCheck->execute([$post['slug']]);
        $exists = $stmtCheck->fetchColumn();

        if (!$exists) {
            // Get category ID
            $catId = 1;
            if (!empty($post['cat_slug'])) {
                $stmtCat = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                $stmtCat->execute([$post['cat_slug']]);
                $res = $stmtCat->fetchColumn();
                if ($res) $catId = $res;
            }

            $jsonContent = json_encode($post['content'], JSON_UNESCAPED_UNICODE);
            $heroImg = $post['hero_image'] ?? '';
            $heroSet = isset($post['hero_settings']) ? json_encode($post['hero_settings'], JSON_UNESCAPED_UNICODE) : '';
            $showToc = $post['show_toc'] ?? 0;
            $showShare = $post['show_share_buttons'] ?? 1;
            $desc = $post['description'] ?? '';
            $thumb = $post['thumbnail'] ?? '';
            $isHideLlms = $post['is_hide_llms'] ?? 0;

            // Generate search text
            $searchText = grinds_generate_search_text($post['title'], $desc, $jsonContent);

            $stmtPost->execute([
                $post['title'],
                $post['slug'],
                $jsonContent,
                $searchText,
                $catId,
                $heroImg,
                $heroSet,
                $showToc,
                $showShare,
                $desc,
                $thumb,
                $isHideLlms,
                $now,
                $now
            ]);

            // Insert post tags
            $postId = $pdo->lastInsertId();
            if (!empty($post['tags']) && $postId) {
                $stmtPostTag = $pdo->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                foreach ($post['tags'] as $tagSlug) {
                    $stmtCheckTag->execute([$tagSlug]);
                    $tagId = $stmtCheckTag->fetchColumn();
                    if ($tagId) {
                        $stmtPostTag->execute([$postId, $tagId]);
                    }
                }
            }
        }
    }

    // Insert templates
    $stmtTpl = $pdo->prepare("INSERT INTO posts (title, slug, content, search_text, type, status, created_at, updated_at) VALUES (?, ?, ?, '', 'template', 'private', ?, ?)");

    foreach ($data['templates'] as $tpl) {
        $exists = $pdo->prepare("SELECT count(*) FROM posts WHERE type='template' AND title = ?");
        $exists->execute([$tpl['title']]);
        if (!$exists->fetchColumn()) {
            $slug = 'tpl-' . bin2hex(random_bytes(6));
            $json = json_encode($tpl['content'], JSON_UNESCAPED_UNICODE);
            // Exclude from search
            $stmtTpl->execute([$tpl['title'], $slug, $json, $now, $now]);
        }
    }

    // Insert menus
    $stmtMenu = $pdo->prepare("INSERT INTO nav_menus (location, label, url, sort_order, target_theme) VALUES (?, ?, ?, ?, 'all')");
    $stmtCheckMenu = $pdo->prepare("SELECT id FROM nav_menus WHERE location = ? AND url = ?");

    if (!empty($data['menus'])) {
        foreach ($data['menus'] as $menu) {
            $stmtCheckMenu->execute([$menu['location'], $menu['url']]);
            if (!$stmtCheckMenu->fetchColumn()) {
                $stmtMenu->execute([$menu['location'], $menu['label'], $menu['url'], $menu['sort']]);
            }
        }
    }

    // Insert widgets
    $stmtWidget = $pdo->prepare("INSERT INTO widgets (type, title, content, settings, sort_order, target_theme, is_active) VALUES (?, ?, ?, ?, ?, 'all', 1)");
    $widgetCount = $pdo->query("SELECT count(*) FROM widgets")->fetchColumn();

    if ($widgetCount == 0 && !empty($data['widgets'])) {
        foreach ($data['widgets'] as $w) {
            $stmtWidget->execute([
                $w['type'],
                $w['title'],
                $w['content'],
                $w['settings'],
                $w['sort_order']
            ]);
        }
    }

    Grinds_InstallKitchenSink($pdo, $lang);

    // Register sample images
    $sampleImages = [
        'assets/uploads/sample_image.svg' => 'Sample Image',
        'assets/uploads/sample_sink.svg' => 'Kitchen Sink Image'
    ];

    $stmtMediaCheck = $pdo->prepare("SELECT id FROM media WHERE filepath = ?");
    $stmtMediaInsert = $pdo->prepare("INSERT INTO media (filename, filepath, file_type, file_size, metadata, uploaded_at) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($sampleImages as $path => $title) {
        $absPath = defined('ROOT_PATH') ? ROOT_PATH . '/' . $path : $path;
        if (file_exists($absPath)) {
            $stmtMediaCheck->execute([$path]);
            if (!$stmtMediaCheck->fetchColumn()) {
                $size = filesize($absPath);
                $meta = json_encode(['title' => $title, 'alt' => $title, 'width' => 800, 'height' => 400], JSON_UNESCAPED_UNICODE);
                $stmtMediaInsert->execute([
                    basename($path),
                    $path,
                    'image/svg+xml',
                    $size,
                    $meta,
                    date('Y-m-d H:i:s')
                ]);
            }
        }
    }
}

/**
 * Retrieve demo data.
 */
function Grinds_GetKitchenSinkData($lang = 'en')
{
    $isJa = ($lang === 'ja');
    $dummyImg = _grinds_ensure_sample_image('sample_sink.svg', 'Kitchen Sink', '#4a90e2', '#ffffff');

    return [
        // Define text and headings
        ['type' => 'header', 'data' => ['text' => $isJa ? '全ブロック表示テスト (Kitchen Sink)' : 'All Blocks Demo (Kitchen Sink)', 'level' => 'h2']],
        ['type' => 'paragraph', 'data' => ['text' => $isJa
            ? 'これはGrindSiteで利用可能なすべてのブロックを表示するテスト記事です。<br>テーマのCSSスタイリングや動作確認にご利用ください。<br><b>太字</b>、<i>斜体</i>、<s>打ち消し</s>、<a href="#">リンク</a>の表示確認。'
            : 'This is a test post displaying all blocks available in GrindSite.<br>Use this to check theme CSS styling and block behavior.<br>Check <b>Bold</b>, <i>Italic</i>, <s>Strike</s>, and <a href="#">Link</a> styles.']],

        ['type' => 'divider', 'data' => []],

        // Define heading levels
        ['type' => 'header', 'data' => ['text' => $isJa ? '見出しレベル H3' : 'Heading Level H3', 'level' => 'h3']],
        ['type' => 'paragraph', 'data' => ['text' => $isJa ? '以下は各見出しレベルの表示サンプルです。' : 'Below are samples of each heading level.']],
        ['type' => 'header', 'data' => ['text' => $isJa ? '見出しレベル H4' : 'Heading Level H4', 'level' => 'h4']],
        ['type' => 'header', 'data' => ['text' => $isJa ? '見出しレベル H5' : 'Heading Level H5', 'level' => 'h5']],
        ['type' => 'header', 'data' => ['text' => $isJa ? '見出しレベル H6' : 'Heading Level H6', 'level' => 'h6']],

        ['type' => 'divider', 'data' => []],

        // Define lists and layouts
        ['type' => 'header', 'data' => ['text' => $isJa ? 'リストとカラム (Lists & Columns)' : 'Lists & Columns', 'level' => 'h2']],
        ['type' => 'columns', 'data' => [
            'ratio' => '1-1',
            'leftText' => $isJa
                ? '<b>箇条書き</b><ul><li>リストアイテム 1</li><li>リストアイテム 2</li><li>リストアイテム 3</li></ul>'
                : '<b>Unordered List</b><ul><li>List Item 1</li><li>List Item 2</li><li>List Item 3</li></ul>',
            'rightText' => $isJa
                ? '<b>番号付き</b><ol><li>ステップ 1</li><li>ステップ 2</li><li>ステップ 3</li></ol>'
                : '<b>Ordered List</b><ol><li>Step 1</li><li>Step 2</li><li>Step 3</li></ol>'
        ]],

        // Define media
        ['type' => 'header', 'data' => ['text' => $isJa ? 'メディア (Media)' : 'Media', 'level' => 'h2']],
        ['type' => 'image', 'data' => ['url' => $dummyImg, 'caption' => $isJa ? '画像ブロック（キャプション）' : 'Image Block (Caption)']],

        ['type' => 'paragraph', 'data' => ['text' => $isJa ? '<b>ギャラリー (3列)</b>' : '<b>Gallery (3 Cols)</b>']],
        ['type' => 'gallery', 'data' => [
            'columns' => '3',
            'images' => [
                ['url' => $dummyImg, 'caption' => $isJa ? '画像 1' : 'Image 1'],
                ['url' => $dummyImg, 'caption' => $isJa ? '画像 2' : 'Image 2'],
                ['url' => $dummyImg, 'caption' => $isJa ? '画像 3' : 'Image 3']
            ]
        ]],

        ['type' => 'paragraph', 'data' => ['text' => $isJa ? '<b>カルーセルスライダー</b>' : '<b>Carousel Slider</b>']],
        ['type' => 'carousel', 'data' => [
            'autoplay' => true,
            'images' => [
                ['url' => $dummyImg, 'caption' => $isJa ? 'スライド 1' : 'Slide 1'],
                ['url' => $dummyImg, 'caption' => $isJa ? 'スライド 2' : 'Slide 2'],
                ['url' => $dummyImg, 'caption' => $isJa ? 'スライド 3' : 'Slide 3']
            ]
        ]],

        ['type' => 'divider', 'data' => []],

        // Define components
        ['type' => 'header', 'data' => ['text' => $isJa ? 'コンポーネント (Components)' : 'Components', 'level' => 'h2']],

        ['type' => 'section', 'data' => ['bgColor' => 'gray', 'text' => $isJa ? '<b>セクション（グレー）</b><br>背景色付きのコンテンツエリアです。' : '<b>Gray Section</b><br>Content area with background color.']],
        ['type' => 'section', 'data' => ['bgColor' => 'blue', 'text' => $isJa ? '<b>セクション（ブルー）</b><br>インフォメーションなどに適しています。' : '<b>Blue Section</b><br>Suitable for information.']],

        ['type' => 'callout', 'data' => ['style' => 'info', 'text' => $isJa ? '<b>情報:</b> これは情報コールアウトです。' : '<b>Info:</b> Information callout.']],
        ['type' => 'callout', 'data' => ['style' => 'success', 'text' => $isJa ? '<b>成功:</b> 処理が完了しました。' : '<b>Success:</b> Process completed.']],
        ['type' => 'callout', 'data' => ['style' => 'warning', 'text' => $isJa ? '<b>注意:</b> 注意が必要です。' : '<b>Warning:</b> Attention required.']],
        ['type' => 'callout', 'data' => ['style' => 'danger', 'text' => $isJa ? '<b>警告:</b> エラーが発生しました。' : '<b>Alert:</b> An error occurred.']],

        ['type' => 'button', 'data' => ['text' => $isJa ? 'プライマリボタン' : 'Primary Button', 'url' => '#', 'color' => 'primary']],
        ['type' => 'button', 'data' => ['text' => $isJa ? '成功ボタン (Green)' : 'Success Button', 'url' => '#', 'color' => 'success']],

        ['type' => 'card', 'data' => [
            'title' => $isJa ? 'リンクカード' : 'Link Card',
            'description' => $isJa ? '外部サイトへのリンクをリッチに表示します。' : 'Displays a rich preview for external links.',
            'url' => '#',
            'image' => $dummyImg
        ]],

        ['type' => 'accordion', 'data' => ['items' => [
            ['title' => $isJa ? 'Q. 質問タイトル 1' : 'Q. Question Title 1', 'content' => $isJa ? 'A. ここに回答が入ります。' : 'A. Answer text goes here.'],
            ['title' => $isJa ? 'Q. 質問タイトル 2' : 'Q. Question Title 2', 'content' => $isJa ? 'A. ここに回答が入ります。' : 'A. Answer text goes here.']
        ]]],

        ['type' => 'price', 'data' => ['items' => [
            ['plan' => $isJa ? 'ベーシック' : 'Basic', 'price' => 'Free', 'features' => $isJa ? "機能A\n機能B" : "Feature A\nFeature B", 'recommend' => false],
            ['plan' => $isJa ? 'プロ' : 'Pro', 'price' => '$29', 'features' => $isJa ? "全機能\n優先サポート" : "All Features\nPriority Support", 'recommend' => true],
            ['plan' => $isJa ? 'エンタープライズ' : 'Enterprise', 'price' => 'Custom', 'features' => $isJa ? "専任サポート\nSLA" : "Dedicated Support\nSLA", 'recommend' => false],
        ]]],

        ['type' => 'step', 'data' => ['items' => [
            ['title' => $isJa ? 'ダウンロード' : 'Download', 'desc' => $isJa ? 'ファイルをダウンロードします。' : 'Download the file.'],
            ['title' => $isJa ? 'インストール' : 'Install', 'desc' => $isJa ? 'インストールします。' : 'Install to server.'],
            ['title' => $isJa ? '開始' : 'Start', 'desc' => $isJa ? '利用を開始します。' : 'Start using it.']
        ]]],

        ['type' => 'testimonial', 'data' => [
            'name' => $isJa ? '山田 太郎' : 'John Doe',
            'role' => $isJa ? 'Webデザイナー' : 'Web Designer',
            'comment' => $isJa ? 'このCMSは非常に使いやすく、デザインも美しいです。' : 'This CMS is very easy to use and has a beautiful design.',
            'image' => ''
        ]],

        ['type' => 'proscons', 'data' => [
            'pros_title' => $isJa ? 'メリット' : 'Pros',
            'pros_items' => [$isJa ? '高速' : 'Fast', $isJa ? '簡単' : 'Easy'],
            'cons_title' => $isJa ? 'デメリット' : 'Cons',
            'cons_items' => [$isJa ? '特になし' : 'None']
        ]],

        ['type' => 'rating', 'data' => ['score' => 4.5, 'max' => 5, 'color' => 'gold']],

        ['type' => 'search_box', 'data' => ['placeholder' => $isJa ? 'サイト内を検索...' : 'Search within site...']],

        ['type' => 'conversation', 'data' => [
            'position' => 'left',
            'name' => 'A',
            'text' => $isJa ? '会話形式のブロックです。' : 'This is a conversation block.',
            'image' => ''
        ]],
        ['type' => 'conversation', 'data' => [
            'position' => 'right',
            'name' => 'B',
            'text' => $isJa ? '右側に配置することも可能です。' : 'It can also be aligned to the right.',
            'image' => ''
        ]],

        ['type' => 'spacer', 'data' => ['height' => 100]],
    ];
}

/**
 * Install Kitchen Sink data (Post, Tags, Menu).
 */
function Grinds_InstallKitchenSink($pdo, $lang = 'en')
{
    $isJa = ($lang === 'ja');
    $now = date('Y-m-d H:i:s');

    // Define data
    $slug = 'kitchen-sink';
    $title = $isJa ? '表示テスト (Kitchen Sink)' : 'Kitchen Sink';
    $content = Grinds_GetKitchenSinkData($lang);
    $tags = ['test', 'kitchen-sink'];

    // Insert tags
    $stmtTag = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
    $stmtCheckTag = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
    $tagIds = [];

    foreach ($tags as $tagSlug) {
        $stmtCheckTag->execute([$tagSlug]);
        $tid = $stmtCheckTag->fetchColumn();
        if (!$tid) {
            $tagName = ucfirst($tagSlug);
            $stmtTag->execute([$tagName, $tagSlug]);
            $tid = $pdo->lastInsertId();
        }
        $tagIds[] = $tid;
    }

    // Insert post
    $stmtCheckPost = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
    $stmtCheckPost->execute([$slug]);
    $postId = $stmtCheckPost->fetchColumn();

    if (!$postId) {
        // Wrap blocks
        $jsonContent = json_encode(['blocks' => $content], JSON_UNESCAPED_UNICODE);
        $stmtPost = $pdo->prepare("INSERT INTO posts (title, slug, content, type, status, published_at, created_at, updated_at) VALUES (?, ?, ?, 'post', 'published', ?, ?, ?)");
        $stmtPost->execute([$title, $slug, $jsonContent, $now, $now, $now]);
        $postId = $pdo->lastInsertId();
    }

    // Link tags
    if ($postId) {
        $stmtLink = $pdo->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
        foreach ($tagIds as $tid) {
            $stmtLink->execute([$postId, $tid]);
        }
    }

    // Add to menu
    $menuUrl = '/' . $slug;
    $stmtCheckMenu = $pdo->prepare("SELECT id FROM nav_menus WHERE url = ?");
    $stmtCheckMenu->execute([$menuUrl]);
    if (!$stmtCheckMenu->fetchColumn()) {
        $stmtMenu = $pdo->prepare("INSERT INTO nav_menus (location, label, url, sort_order, target_theme) VALUES (?, ?, ?, ?, 'all')");
        // Add to footer
        $stmtMenu->execute(['footer', $title, $menuUrl, 99]);
    }

    return $postId;
}

/**
 * Helper to generate sample SVG image.
 */
function _grinds_ensure_sample_image($filename, $text, $bg, $fill)
{
    $dummyImg = defined('PLACEHOLDER_IMG') ? PLACEHOLDER_IMG : '';

    if (defined('ROOT_PATH')) {
        $relPath = 'assets/uploads/' . $filename;
        $absPath = ROOT_PATH . '/' . $relPath;

        if (file_exists($absPath)) {
            return $relPath;
        }

        $dir = dirname($absPath);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        if (is_writable($dir)) {
            $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 400" width="800" height="400"><rect width="800" height="400" fill="' . $bg . '"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="48" fill="' . $fill . '">' . $text . '</text></svg>';
            if (@file_put_contents($absPath, $svgContent)) {
                return $relPath;
            }
        }
    }
    return $dummyImg;
}
