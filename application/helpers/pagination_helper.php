<?php

if (!defined('pagination_helper')) {
    define('pagination_helper', TRUE);

    function initialize_pagination($base_url, $total_rows, $uri_segment) {
        $CI = & get_instance();
        $CI->load->library('pagination');

        $config['base_url'] = $base_url;
        $config['total_rows'] = $total_rows;
        $config['per_page'] = PAGINATION_RESULTS_PER_PAGE;
        $config['uri_segment'] = $uri_segment;
        $config['num_links'] = 1;
        $config['use_page_numbers'] = TRUE;
        $config['suffix'] = '?'.($_SERVER['QUERY_STRING']);

        if (empty($_SERVER['QUERY_STRING'])) {
            $config['first_url'] = $base_url;
        } else {
            $config['first_url'] = $base_url.'?'.($_SERVER['QUERY_STRING']);
        }

        $config['cur_tag_open'] = "<li class='active''><a href=''>";
        $config['cur_tag_close'] = "</a></li>";

        $CI->pagination->initialize($config);

        $links = $CI->pagination->create_links();

        return $links;
    }
}