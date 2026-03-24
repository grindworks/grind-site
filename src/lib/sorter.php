<?php

/**
 * Handle column sorting logic.
 */
if (!defined('GRINDS_APP')) exit;

class Sorter
{
    private $sort;
    private $order;
    private $allowed_columns;

    /** Initialize sorter. */
    public function __construct($allowed_columns, $default_sort = 'id', $default_order = 'DESC')
    {
        $this->allowed_columns = $allowed_columns;
        $params = Routing::getParams();
        $sort_req = Routing::getString($params, 'sort', $default_sort);
        $order_req = strtoupper(Routing::getString($params, 'order', $default_order));

        // Sanitize parameters
        $this->sort = in_array($sort_req, $allowed_columns) ? $sort_req : $default_sort;
        $this->order = ($order_req === 'ASC' || $order_req === 'DESC') ? $order_req : $default_order;
    }

    /** Generate ORDER BY clause. */
    public function getOrderClause()
    {
        return "ORDER BY {$this->sort} {$this->order}";
    }

    /** Render sortable header. */
    public function renderTh($key, $label, $extra_class = '')
    {
        $is_current = ($this->sort === $key);
        $next_order = ($is_current && $this->order === 'DESC') ? 'ASC' : 'DESC';

        $params = Routing::getParams();
        $params['sort'] = $key;
        $params['order'] = $next_order;
        $params['page'] = 1;
        $url = '?' . http_build_query($params);

        $th_class = 'px-6 py-4 cursor-pointer hover:bg-theme-bg hover:text-theme-primary transition-colors group select-none whitespace-nowrap';
        if (!empty($extra_class)) {
            $th_class .= ' ' . $extra_class;
        }
        $icon = '';

        if ($is_current) {
            // Apply active styles
            $th_class .= ' bg-theme-bg/50 text-theme-primary font-bold';
            $iconName = ($this->order === 'ASC') ? 'outline-chevron-up' : 'outline-chevron-down';
            $icon = '<svg class="w-4 h-4 ml-1 text-theme-primary inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . h(grinds_asset_url('assets/img/sprite.svg') . '#' . $iconName) . '"></use></svg>';
        } else {
            // Apply inactive styles
            $th_class .= ' text-theme-text opacity-70 hover:opacity-100';
            $icon = '<svg class="w-4 h-4 ml-1 text-theme-text opacity-0 group-hover:opacity-30 inline-block transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . h(grinds_asset_url('assets/img/sprite.svg') . '#outline-chevron-down') . '"></use></svg>';
        }

        echo "<th class=\"{$th_class}\" onclick=\"location.href='" . h($url) . "'\">";
        echo "<div class=\"flex items-center justify-between gap-1\"><span>{$label}</span>{$icon}</div>";
        echo "</th>";
    }
}
