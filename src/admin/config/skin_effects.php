<?php

/**
 * skin_effects.php
 *
 * Define CSS effects and components for the admin skin system.
 */

// Define animations
$kf_flicker = '@keyframes flicker { 0% { opacity: 0.97; } 50% { opacity: 1; } 100% { opacity: 0.98; } }';
$kf_flicker_fast = '@keyframes flicker_fast { 0% { opacity: 0.9; } 100% { opacity: 1; } }';

// Define CSS components
$css_input_glow_base = '
    input:focus, textarea:focus, select:focus {
        border-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
        box-shadow: 0 0 10px rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.4)) !important;
        outline: none;
    }
';
$css_btn_glow_base = '
    .btn-primary:hover { box-shadow: 0 0 15px rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.5)) !important; }
';
$css_nav_border_base = '
    .skin-sidebar-item { border-left: 4px solid transparent; transition: all 0.2s; }
    .skin-sidebar-item:hover { border-left-color: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.5)); background: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.05)); }
    .skin-sidebar-item.active { border-left-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)); background: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.1)); }
';

return [
    // 1. Layout and surface

    'card_lift' => '
        .card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    ',
    // Glass texture (Modern)
    'card_glass_modern' => '
        .bg-theme-surface, .card, .modal-content {
            background-color: rgb(var(--color-surface) / calc(var(--color-surface-alpha, 1) * 0.7)) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgb(var(--color-border) / calc(var(--color-border-alpha, 1) * 0.3));
        }
    ',
    // Glass texture (Cyber/Dark)
    'card_glass_cyber' => '
        .bg-theme-surface, .card, .modal-content {
            background-color: rgb(var(--color-surface) / calc(var(--color-surface-alpha, 1) * 0.9)) !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.5)) !important;
            box-shadow: inset 0 0 20px rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.1)) !important;
        }
    ',
    'surface_bevel' => '
        .bg-theme-surface, .card { border-top: 2px solid rgba(255,255,255,0.1); border-left: 2px solid rgba(255,255,255,0.1); border-right: 2px solid rgba(0,0,0,0.2); border-bottom: 2px solid rgba(0,0,0,0.2); }
    ',
    'surface_inset' => '
        .bg-theme-surface { box-shadow: inset 0 0 20px rgb(var(--color-text) / calc(var(--color-text-alpha, 1) * 0.05)); border: 1px solid rgb(var(--color-border) / var(--color-border-alpha, 1)); }
    ',

    // 2. Borders and brackets

    'border_double' => '
        .bg-theme-surface, .card {
            border: 1px solid rgb(var(--color-border) / var(--color-border-alpha, 1));
            box-shadow: inset 0 0 0 2px rgb(var(--color-bg) / var(--color-bg-alpha, 1)), inset 0 0 0 4px rgb(var(--color-border) / var(--color-border-alpha, 1));
        }
    ',
    'corner_brackets' => '
        .card, .panel, .modal-content { clip-path: polygon(0 10px, 10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%); }
        .card::after, .panel::after {
            content: ""; position: absolute; bottom: 0; right: 0;
            width: 20px; height: 20px;
            border-right: 3px solid rgb(var(--color-primary) / var(--color-primary-alpha, 1)); border-bottom: 3px solid rgb(var(--color-primary) / var(--color-primary-alpha, 1));
            opacity: 0.6;
        }
    ',
    'no_radius' => ' * { border-radius: 0 !important; } ',

    // 3. Inputs

    'input_glow_modern' => $css_input_glow_base,
    'input_underline' => '
        input:not([type="checkbox"]):not([type="radio"]), textarea, select { border-width: 0 0 2px 0 !important; border-radius: 0 !important; background: transparent !important; }
        input:not([type="checkbox"]):not([type="radio"]):focus, textarea:focus, select:focus { border-bottom-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; box-shadow: none !important; background: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.05)) !important; }
    ',
    'input_solid' => '
        input:focus, textarea:focus, select:focus { outline: 2px solid rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; box-shadow: none !important; }
    ',
    // Require 'Roboto Mono' font
    'input_mono' => '
        input, textarea, select { font-family: "Roboto Mono", monospace; }
    ',
    // Require 'Courier Prime' font
    'input_typewriter' => '
        input, textarea, select { font-family: "Courier Prime", "Courier New", monospace; }
    ',
    'input_sunken' => '
        input, textarea, select {
            border: 2px solid rgba(0, 0, 0, 0.8) !important;
            box-shadow: inset 4px 4px 0px rgba(0, 0, 0, 0.6), inset -2px -2px 0px rgba(255, 255, 255, 0.15), 2px 2px 0px rgba(255, 255, 255, 0.1) !important;
            background: rgba(0, 0, 0, 0.3) !important;
            border-radius: 0 !important;
            transition: none !important;
        }
        input:focus, textarea:focus, select:focus {
            border-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            box-shadow: inset 4px 4px 0px rgba(0, 0, 0, 0.8), 0 0 0 2px rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            background: rgba(0, 0, 0, 0.5) !important;
            outline: none !important;
        }
        input[type="checkbox"]:checked, input[type="radio"]:checked {
            background-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            box-shadow: inset 4px 4px 0px rgba(0, 0, 0, 0.6), inset -2px -2px 0px rgba(255, 255, 255, 0.15), 2px 2px 0px rgba(255, 255, 255, 0.1) !important;
            border-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
        }
    ',
    'input_retro_box' => '
        input, textarea, select {
            border: 2px solid rgb(var(--color-input-border) / var(--color-input-border-alpha, 1)) !important;
            background: rgb(var(--color-input-bg) / var(--color-input-bg-alpha, 1)) !important;
            color: rgb(var(--color-input-text) / var(--color-input-text-alpha, 1)) !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        input:focus, textarea:focus, select:focus { background: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.1)) !important; }
        input[type="checkbox"]:checked, input[type="radio"]:checked {
            background-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            border-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            accent-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1));
        }
    ',

    // 4. Buttons

    'btn_glow_modern' => $css_btn_glow_base,
    'btn_lift' => ' .btn-primary { transition: transform 0.2s; } .btn-primary:hover { transform: translateY(-2px); } ',
    'btn_gradient' => ' .btn-primary { background-image: linear-gradient(135deg, rgb(var(--color-primary) / var(--color-primary-alpha, 1)) 0%, color-mix(in srgb, rgb(var(--color-primary) / var(--color-primary-alpha, 1)), rgb(var(--color-text) / var(--color-text-alpha, 1)) 20%) 100%); border: none; } ',
    'btn_uppercase' => ' .btn-primary, .btn-secondary, button { text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; } ',
    'btn_3d_pixel' => '
        .btn-primary {
            box-shadow: inset -4px -4px 0px 0px rgba(0,0,0,0.5), inset 4px 4px 0px 0px rgba(255,255,255,0.3);
            border: none !important;
        }
        .btn-primary:active {
            box-shadow: inset 4px 4px 0px 0px rgba(0,0,0,0.5), inset -4px -4px 0px 0px rgba(255,255,255,0.3);
            transform: translateY(2px);
        }
    ',
    'btn_8bit' => '
        .btn-primary { border: 2px solid rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; box-shadow: 3px 3px 0px rgb(var(--color-primary) / var(--color-primary-alpha, 1)); transition: transform 0.1s; } .btn-primary:active { transform: translate(2px, 2px); box-shadow: 1px 1px 0px rgb(var(--color-primary) / var(--color-primary-alpha, 1)); }
    ',

    // 5. Navigation

    'nav_border_left_modern' => $css_nav_border_base,
    'nav_cyber' => '
        .skin-sidebar-item { margin-bottom: 4px; position: relative; clip-path: polygon(0% 0%, 95% 0%, 100% 50%, 95% 100%, 0% 100%); transition: all 0.3s; border-left: 3px solid transparent !important; }
        .skin-sidebar-item:hover { background: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.1)) !important; border-left-color: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.5)) !important; color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; }
        .skin-sidebar-item.active { background: linear-gradient(90deg, rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.3)) 0%, transparent 100%) !important; border-left-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; text-shadow: 0 0 8px rgb(var(--color-primary) / var(--color-primary-alpha, 1)); }
    ',
    'sidebar_crt' => '
        .bg-theme-sidebar {
            background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgb(var(--color-sidebar-crt) / var(--sidebar-crt-opacity, 0.05)) 2px, rgb(var(--color-sidebar-crt) / var(--sidebar-crt-opacity, 0.05)) 4px);
            box-shadow: inset var(--sidebar-crt-shadow-x, -15px) 0 var(--sidebar-crt-shadow-blur, 30px) rgb(var(--color-sidebar-crt-shadow) / var(--sidebar-crt-shadow-opacity, 0.9)) !important;
            border-right: var(--sidebar-crt-border-width, 1px) solid rgb(var(--color-sidebar-crt-border) / var(--sidebar-crt-border-opacity, 0.2)) !important;
        }
    ',
    'sidebar_gradient_metal' => '
        .bg-theme-sidebar {
            background-image: linear-gradient(180deg, #2c2c2c 0%, #111111 100%) !important;
            box-shadow: inset -1px 0 0 rgba(255,255,255,0.05) !important;
            border-right: 1px solid #000 !important;
        }
    ',
    'nav_minimal' => '
        .skin-sidebar-item { border: none !important; opacity: 0.7; } .skin-sidebar-item.active { opacity: 1; font-weight: bold; }
    ',

    // Navigation shapes

    // Cyber cut
    'nav_shape_cyber' => '
        .skin-sidebar-item {
            border-radius: 0px !important;
            clip-path: polygon(0 0, 100% 0, 100% 70%, 90% 100%, 0 100%);
            margin-bottom: 6px;
            border-left: 2px solid transparent !important;
            border-left: none !important;
            box-shadow: inset 0 0 0 0 transparent;
        }
        .skin-sidebar-item.active {
            border-left-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            box-shadow: inset 4px 0 0 0 rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            padding-left: 1.5rem !important;
        }
    ',

    // 6. Typography

    'text_gradient' => ' h1, h2, h3 { background: linear-gradient(135deg, rgb(var(--color-primary) / var(--color-primary-alpha, 1)), rgb(var(--color-secondary) / var(--color-secondary-alpha, 1))); -webkit-background-clip: text; background-clip: text; color: transparent !important; display: inline-block; } ',
    // Require 'Cinzel Decorative' font
    'heading_decorative' => ' h1, h2, h3, h4, h5, h6 { font-family: "Cinzel Decorative", "Playfair Display", serif; font-weight: 700; letter-spacing: 0.05em; } ',
    // Bloom effect
    'text_3d_shadow' => ' h1, h2, h3 { text-shadow: 2px 2px 0px rgb(var(--color-text) / calc(var(--color-text-alpha, 1) * 0.25)); } ',
    'text_bloom_modern' => ' body { text-shadow: 0 0 4px rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.6)); } ',
    // Bloom effect (Neon)
    'text_bloom_cyber' => ' .text-theme-primary, h1, h2, .font-bold { text-shadow: 0 0 8px rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.4)); } ',

    // 7. Misc and overlays

    // Scanline
    'bg_dither_gradient' => ' body { background-image: radial-gradient(rgb(var(--color-text) / calc(var(--color-text-alpha, 1) * 0.1)) 1px, transparent 1px); background-size: 4px 4px; } ',
    'overlay_scanline' => '
        body::after {
            content: " "; display: block; position: fixed; top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18,16,16,0) 50%, rgba(0,0,0,0.1) 50%),
                        linear-gradient(90deg, rgba(255,0,0,0.06), rgba(0,255,0,0.02), rgba(0,0,255,0.06));
            z-index: 9999; background-size: 100% 2px, 3px 100%; pointer-events: none;
        }
    ',
    // Scanline (Thin)
    'overlay_scanline_thin' => '
        body::before {
            content: " "; display: block; position: fixed; top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%),
                        linear-gradient(90deg, rgba(255, 0, 0, 0.03), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.03));
            z-index: 9999; background-size: 100% 3px, 3px 100%; pointer-events: none;
        }
    ',
    // Cinematic effect
    'cinematic_fx' => '
        body::after {
            content: " "; display: block; position: fixed; top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.05) 50%),
                        linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02));
            z-index: 9999; background-size: 100% 4px, 4px 100%; pointer-events: none;
        }
    ',
    // CRT flicker
    'crt_flicker' => $kf_flicker . ' body { animation: flicker 0.15s infinite; } ',
    // CRT flicker (Animated)
    'crt_flicker_full' => '
        ' . $kf_flicker_fast . '
        body::after {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.05) 50%),
                        linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02));
            z-index: 9999; background-size: 100% 2px, 3px 100%; pointer-events: none;
        }
        .text-theme-primary { animation: flicker_fast 0.01s infinite alternate; }
    ',
    'scrollbar_custom' => '
        ::-webkit-scrollbar { width: 10px; background: rgb(var(--color-bg) / var(--color-bg-alpha, 1)); }
        ::-webkit-scrollbar-thumb { background: rgb(var(--color-primary) / var(--color-primary-alpha, 1)); border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: rgb(var(--color-text) / var(--color-text-alpha, 1)); }
    ',
    'focus_dashed' => ' *:focus-visible { outline: 2px dashed rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; outline-offset: 4px; box-shadow: none !important; } ',

    // Screen recess
    'screen_recess' => '
        .bg-theme-surface, .card {
            box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.9), 0 0 0 2px rgb(var(--color-border) / var(--color-border-alpha, 1)) !important;
            border: 1px solid rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.3)) !important;
        }
    ',

    // Analog text glitch
    'text_analog_glitch' => '
        h1, h2, h3, .font-bold {
            text-shadow: 1px 1px 0px rgb(var(--color-danger) / calc(var(--color-danger-alpha, 1) * 0.5)), -1px -1px 0px rgb(var(--color-info) / calc(var(--color-info-alpha, 1) * 0.5)), 0 0 5px rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.5));
        }
    ',

    // Industrial dot navigation
    'nav_terminal' => '
        .skin-sidebar-item { border: 1px solid transparent; margin-bottom: 2px; }
        .skin-sidebar-item.active {
            border: 1px solid rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            background: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.1)) !important;
            position: relative;
        }
        .skin-sidebar-item.active::before {
            content: ">"; position: absolute; left: 8px; font-weight: bold;
        }
        .skin-sidebar-item.active span { padding-left: 12px; }
    ',

    // Decorative serif headings
    // Require 'Cinzel' font
    'text_luxury_serif' => '
        h1, h2, h3 { font-family: "Cinzel", serif !important; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; }
        h2 { border-bottom: 3px double rgb(var(--color-primary) / var(--color-primary-alpha, 1)); padding-bottom: 0.5rem; }
    ',

    // Gold double trim
    'border_luxury_gold' => '
        .bg-theme-surface, .card {
            border: 1px solid rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            box-shadow: 0 0 0 2px rgb(var(--color-bg) / var(--color-bg-alpha, 1)), 0 0 0 3px rgb(var(--color-primary) / var(--color-primary-alpha, 1)), 0 10px 30px rgba(0,0,0,0.5) !important;
        }
    ',

    // Luxury outline button
    'btn_luxury_outline' => '
        .btn-primary {
            background: transparent !important;
            border: 2px solid rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            transition: all 0.4s ease;
        }
        .btn-primary:hover {
            background: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
            color: rgb(var(--color-on-primary) / var(--color-on-primary-alpha, 1)) !important;
            box-shadow: 0 0 20px rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.4)) !important;
        }
    ',

    // Gold selection
    'selection_gold' => '
        ::selection { background: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; color: rgb(var(--color-on-primary) / var(--color-on-primary-alpha, 1)) !important; }
        ::-moz-selection { background: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important; color: rgb(var(--color-on-primary) / var(--color-on-primary-alpha, 1)) !important; }
    ',

];
