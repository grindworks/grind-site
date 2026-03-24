<?php

/**
 * skin_assets.php
 *
 * Define allowed assets for admin skins (security whitelist).
 */
return [
    'textures' => [
        'none' => '',
        'dots' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiMwMDAwMDAiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==',
        'grid' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTIwIDBMMCAwTDAgMjAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwMDAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDUiIHN0cm9rZS13aWR0aD0iMSIvPjwvc3ZnPg==',
        'noise' => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxmaWx0ZXIgaWQ9Im4iPjxmZVR1cmJ1bGVuY2UgdHlwZT0iZnJhY3RhbE5vaXNlIiBiYXNlRnJlcXVlbmN5PSIwLjciIHN0aXRjaFRpbGVzPSJzdGl0Y2giLz48L2ZpbHRlcj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ0cmFuc3BhcmVudCIvPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiMwMDAwMDAiIG9wYWNpdHk9IjAuMDI1IiBmaWx0ZXI9InVybCgjbikiLz48L3N2Zz4=',
        'pattern_diamond' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTEwIDBMMjAgMTBMMTAgMjBMMCAxMCBaIiBmaWxsPSJub25lIiBzdHJva2U9IiNmZmZmZmYiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3N2Zz4=',
        'stars' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTEyIC41bDMuMDkgNi4yNkwyMiA3LjhjLTEuMTEgMS4wOC0yLjYyIDIuNTUtMy4yOCA0LjA0bC43OCA2LjY2TDEyIDE1LjRsLTUuNSAyLjlMLjA4IDE0LjUxbDQuNzItNC42TDQuNDcgNy44IDYuODIgNy44eiIgZmlsbD0iI2ZmZmZmZiIgZmlsbC1vcGFjaXR5PSIwLjAzIiB0cmFuc2Zvcm09InNjYWxlKDAuNSkiLz48L3N2Zz4=',
        'scanlines' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNCIgaGVpZ2h0PSI0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0wIDBoNHYxaC00eiIgZmlsbD0iIzAwMDAwMCIgZmlsbC1vcGFjaXR5PSIwLjUiLz48L3N2Zz4=',
        'tech_grid' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSA0MCAwIEwgMCAwIDAgNDAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgwLDI0MywyNTUsMC4wNSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==',
        'milano_grid' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTMwIDBMMCAzME02MCAzME0zMCA2MEw2MCAzMCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDAsMjEyLDI1NSwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9zdmc+',
    ],

    'media_backgrounds' => [
        'default' => 'background-color: #f8fafc;',
        'checker_light' => 'background-color: #ffffff; background-image: linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%, #f0f0f0), linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%, #f0f0f0); background-size: 20px 20px; background-position: 0 0, 10px 10px;',
        'checker_dark' => 'background-color: #050505; background-image: linear-gradient(45deg, #0f0f0f 25%, transparent 25%, transparent 75%, #0f0f0f 75%, #0f0f0f), linear-gradient(45deg, #0f0f0f 25%, transparent 25%, transparent 75%, #0f0f0f 75%, #0f0f0f); background-size: 20px 20px; background-position: 0 0, 10px 10px;',
        'grid_dark' => 'background-color: #1a1a1a; background-image: linear-gradient(#333 1px, transparent 1px), linear-gradient(90deg, #333 1px, transparent 1px); background-size: 20px 20px;',
        'grid_purple' => 'background-color: #0f0518; background-image: linear-gradient(#4c1d95 1px, transparent 1px), linear-gradient(90deg, #4c1d95 1px, transparent 1px); background-size: 20px 20px;',
        'dots_light' => 'background-color: #f8fafc; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 10px 10px;',
        'dots_dark' => 'background-color: #0f172a; background-image: radial-gradient(#334155 1px, transparent 1px); background-size: 10px 10px;',
        'terminal_green' => 'background-color: #000a00; background-image: radial-gradient(rgba(0, 150, 0, 0.15) 1px, transparent 0); background-size: 4px 4px;',
        'terminal_amber' => 'background-color: #0a0500; background-image: linear-gradient(rgba(255, 160, 0, 0.05) 1px, transparent 0); background-size: 100% 3px;',
        'high_tech' => 'background-color: #05070a; background-image: radial-gradient(circle, #1e293b 1px, transparent 1px); background-size: 30px 30px;',
    ]
];
