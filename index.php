<?php
/*
Plugin Name: Intramed cursus table
Plugin URI: https://github.com/PH-F/intramed-cursus-table
Description: ACF cursus table for Intramed.
Version: 1.0.0
Author: @PH-F
Author URI: https://github.com/PH-F
License: MIT
License URI: http://opensource.org/licenses/MIT
*/

/**
 * Weergeven van de cursus tabel
 * @param $atts
 * @return void
 */
function wpc_intramed_cursus_table($atts)
{
    $i = 0;

    $table = "<table class='cursusTable'>";
    $table .= sprintf('<thead><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>&nbsp;</th></tr></thead>', "Datum", "Tijd", "Locatie", "Beschikbare plaatsen");
    $table .= "<tbody>";

    while (has_sub_field("data")) {

        $current = strtotime(date("Ymd"));
        $start_date = strtotime(get_sub_field("datum_van"));
        $row = [];
        if ($start_date > $current) {
            $i++;

            $row[0] = get_sub_field("datum_van");
            if (get_sub_field('tussendata')) {
                foreach (get_sub_field('tussendata') as $date) {
                    $row[0] .= '<br>' . $date['datum'];
                }
            }
            if (get_sub_field("datum_tot")) {
                $row[0] .= '<br>' . get_sub_field("datum_tot");
            }

            $row[1] = get_sub_field("tijd");
            $row[2] = explode(';', get_sub_field("location"))[0];
            $row[3] = 'VOL';

            if (!(get_sub_field('bezette_plaatsen') >= get_sub_field('aantal_plaatsen'))) {
                $row[3] = (get_sub_field('aantal_plaatsen') - get_sub_field('bezette_plaatsen'));
            }

            if (!(get_sub_field('bezette_plaatsen') >= get_sub_field('aantal_plaatsen'))) {
                $form = get_field("inschrijfformulier");
                $location = explode(';', get_sub_field("location"));
                $args = array();
                $args["post_id"] = get_the_ID();
                if (isset($form["id"])) {
                    $args["form_id"] = $form["id"];
                }

                $args["datum_van"] = urlencode(get_sub_field("datum_van"));
                $args["datum_tot"] = urlencode(get_sub_field("datum_tot"));
                $args["locatie"] = urlencode(str_replace(' ', '_', $location[1]));
                $args["tijd"] = urlencode(str_replace(' ', '', get_sub_field("tijd")));
                $args["adresgegevens"] = urlencode(get_sub_field("adresgegevens"));
                $args["event"] = urlencode(get_the_title(get_the_ID()));
                if (!empty(get_sub_field("creatio_id"))) {
                    $args['creatio_id'] = sanitize_text_field(get_sub_field("creatio_id"));
                }

                $row[4] = '<a class="button" href="/inschrijven?' . http_build_query($args) . '">Inschrijven</a>';
            }
            $table .= sprintf('<tr><td data-label="Datum">%s</td><td data-label="Tijd">%s</td><td data-label="Locatie">%s</td><td data-label="Beschikbaar">%s</td><td>%s</td></tr>', $row[0], $row[1], $row[2], $row[3], $row[4]);

        }

    }

    $table .= "</tbody>";
    $table .= "</table>";

    if ($i == 0) {
        $table = 'Momenteel staat er geen cursus gepland maar binnenkort volgen nieuwe data.';
    }

    echo $table;
    return;
}

/**
 * Weergeven van het inschrijfformulier
 * @param $atts
 * @return void
 */
function wpc_intramed_cursus_form($atts)
{
    $main_id = (isset($_GET["post_id"])) ? esc_attr(urldecode($_GET["post_id"])) : get_the_ID();
    $form_id = (isset($_GET["form_id"])) ? esc_attr(urldecode($_GET["form_id"])) : null;
    $header_titel = get_field("header_titel");
    $header_intro = get_field("header_intro");

    $header = "";
    if ($header_titel or $header_intro) {
        $header = "<div class='titleBox'><div class='center'><div class='holder'><div class='left'>";
        $header .= "<h1>" . $header_titel . "</h1>";
        $header .= "<div class='border'><span class='ball'></span></div></div>";
        $header .= "<div class='right'>";
        $header .= $header_intro;
        $header .= "</div>";
        $header .= "</div></div></div>";
    }

    $content = "";
    if ($form_id) {
        $content = "<div class='contentBox'>";
        $content .= "<div class='vc_row'>";
        $content .= "<div class='content-holder no-bottom'>";
        $content .= "<h1>Inschrijven</h1>";
        $content .= "<p>" . sprintf('Betreft <strong>%s</strong>', get_the_title($main_id)) . "</p>";
        $content .= "</div>";
        $content .= getForm($form_id);
        $content .= "</div>";
        $content .= "<br /><br />";
        $content .= "</div>";
    }

    echo $header;
    echo $content;
    return;
}

/**
 * Weergeven van de downloads
 * @param $attr
 * @return string
 */
function wpc_intramed_downloads($attr)
{
    $paged = (get_query_var("paged")) ? get_query_var("paged") : 1;
    $filter = (isset($_GET["filter"]) and $_GET["filter"] != "") ? esc_attr($_GET["filter"]) : null;
    $subfilter = (isset($_GET["subfilter"]) and $_GET["subfilter"] != "") ? esc_attr($_GET["subfilter"]) : null;
    $search = (isset($_GET["search"]) and $_GET["search"] != "") ? esc_attr($_GET["search"]) : null;
    $main_id = get_the_ID();
    $ret = '';

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && false === empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

        $ret .= ' -- ';

    } else {

        $ret .= '<script src="https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js"></script>';
        $ret .= '<link href="https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css" rel="stylesheet">';
        $ret .= '<div id="contentCntr">';

        $strTerms =  getTermsFilter(get_terms("dlm_download_category", ["parent" => 0]), $filter, $subfilter, $main_id);

        //start box
        $ret .= '<div class="startBox sub">
            <div class="center">
            <form id="filterForm" method="get" action="' . esc_url(get_permalink($main_id)) . '">
                <div class="holder">
                ' . $strTerms . '
                <div class="right">
                    <h2>Zoek door downloads</h2>
                    <fieldset>
                        <input type="text" name="search" placeholder="Zoekwoord(en)" value="' . $search . '">
                        <input type="submit" value="Zoeken">
                    </fieldset>
                </div>
            </form>
        </div>
        </div>';

        //Disable the relevanssi plugin to be able to search.
        remove_filter( 'posts_request', 'relevanssi_prevent_default_request' );
        remove_filter( 'posts_pre_query', 'relevanssi_query', 99 );

        //downloads
        $args = [
            'posts_per_page' => 10,
            'paged' => $paged,
            'post_count' => -1,
            'post_type' => 'dlm_download',
            'orderby' => 'date',
            'order' => 'desc',
        ];

        if ($search) {
            $args["s"] = $search;
        }

        if ($filter) {
            $args["dlm_download_category"] = $filter;
        }

        if ($subfilter) {
            $args["dlm_download_category"] = $subfilter;
        }

        $loop = new wp_query($args);
        if ($loop->have_posts()) {
            $pager = '';
            $prev = (($paged - 1) >= 0) ? $paged - 1 : false;
            $next = (($paged + 1) <= $loop->max_num_pages) ? $paged + 1 : false;

            $html = '<div class="downloadBox"><div class="center">';
            $html .= '<table class="responsive-table1 large-only stacktable download-overview">';
            $td = $search ? sprintf("Er zijn %s resultaten gevonden voor <strong>‘%s’</strong>", $loop->found_posts, $search) : 'Er zijn ' . $loop->found_posts . ' resultaten gevonden';
            $html .= '<thead><tr><th><em>' . $td. '</em></th><th>Onderwerp</th><th>Versie</th><th>Bestand</th><th>Download</th></tr></thead>';
            $html .= '<tbody>';
            while ($loop->have_posts()) {
                $loop->the_post();

                $download = new DLM_Download();
                $download->set_id(get_the_ID());
                $filename = $download->get_version()->get_filename();
                $extension = explode(".", $filename);
                $extension = $extension[count($extension) - 1];

                $terms = wp_get_post_terms(get_the_ID(), "dlm_download_category");
                $term = [];
                if($terms){
                    $term = array_map(function($term){
                        return $term->name;
                    }, $terms);
                }

                $html .= '<tr class="hover">';
                $html .= '<td class="clickable">' . get_the_title() . '</td>';
                $html .= '<td>' . implode(', ', $term) . '</td>';
                $html .= '<td class="clickable">' . $download->get_version()->get_version_number() . '</td>';
                $html .= '<td><img alt="" src="/wp-content/plugins/intramed-cursus-table/assets/images/icon-'. $extension.'.png" >.'. $extension.'</td></td>';
                $html .= '<td class="clickable"><a class="download-link" href="#" rel="' . $download->get_the_download_link() . '">Download</a></td>';
                $html .= '</tr>';
                $html .= '<tr><td colspan="5"></td></tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div></div>';
            $html .= "<script> jQuery(document).ready(function () {
                jQuery('table.download-overview tbody tr td.clickable').on('click', function (event) {
                var download_url = jQuery('a.download-link', jQuery(this).parent()).attr('rel');
                window.open(download_url, '_blank');
            }); });</script>";
            $html .= '<div class="pagingBox">';
            $html .= '<div class="center">';
            $html .= '<ul>';
            if ($prev) {
                $html .= '<li><a href="' . add_query_arg("paged", $prev) . '">&laquo; Vorige</a></li>';
            }
            for ($i = 1; $i <= $loop->max_num_pages; $i++) {
                if (($paged < 3 and $i < 6) or ($paged - 2 == $i) or ($paged - 1 == $i) or ($paged == $i) or ($paged + 1 == $i) or ($paged + 2 == $i)) {
                    $html .= "<li " . ($paged == $i ? 'class="active"' : '') . "><a href=\"" . add_query_arg("paged", $i) . "\">" . $i . "</a></li>";
                }
            }
            if ($next) {
                $html .= '<li><a href="' . add_query_arg("paged", $next) . '">Volgende &raquo;</a></li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';


        } else {
            $html = '<div class="contentBox">
        <div class="center">
            <div class="content-holder">
                <h1>Geen resultaten</h1>
                <p>Er zijn geen resultaten voor uw zoekterm.</p>
            </div>
        </div>
    </div>';
        }

        $ret .= '</div>';

        $ret .= "<script> jQuery(document).ready(function () {
                jQuery('#filterForm select').chosen();
        });</script>";

        $ret .= '<div id="downloads">' . $html . '</div>';

        wp_reset_query();
//        $ret .= the_content();
    }

    return $ret;
}

/**
 * Download terms filter
 * @param $terms
 * @param $filter
 * @param $subfilter
 * @param $main_id
 * @return string
 */
function getTermsFilter($terms, $filter, $subfilter, $main_id)
{
    $ret = '';
    if ($terms) {

        $options = '';
        foreach ($terms as $term) {
            $options .= '<option value="' . $term->slug . '" ' . ($filter == $term->slug ? " selected=\"selected\"" : "") . '>' . $term->name . '</option>';
        }

        $ret = '<div class="left">
    <h2><a id="remove-filters" class="remove" href="' . get_permalink($main_id) . '">Wis filters</a>Filter downloads</h2>
    <fieldset>
    <select id="filter" onchange="jQuery(\'form#filterForm\').submit();" id="filter" name="filter" data-placeholder="Choose an option" class="chosen-select-no-single">
    <option value="">Kies een categorie</option>
    ' . $options . '
    </select> 
    <div id="subfilter">
    ' . getSubfilter($filter, $subfilter) . '
    </div>
    </fieldset>
    <div class="border"><div class="ball"></div></div>
</div>';
    }

    return  $ret;
}

/**
 * Subfilter
 * @param $filter
 * @param $terms
 * @param $subfilter
 * @param $options2
 * @return string
 */
function getSubfilter($filter, $subfilter)
{
    $sub = '';
    $options2 = '';
    if ($filter) {
        $main_term = get_term_by("slug", $filter, "dlm_download_category");
        $terms = get_terms("dlm_download_category", ["parent" => $main_term->term_id]);
        foreach ($terms as $term) {
            $options2 .= '<option value="' . $term->slug . '" ' . ($subfilter == $term->slug ? " selected=\"selected\"" : "") . '>' . $term->name . '</option>';
        }
        if ($terms) {
            $sub = '<div class="tree">
                <select onchange="jQuery(\'form#filterForm\').submit();" name="subfilter" data-placeholder="Choose an option" class="chosen-select-no-single">
                    <option value="">Kies een onderwerp binnen ' . $main_term->name . '</option>
                    ' . $options2 . '
                </select>
            </div>';
        }
    }
    return $sub;
}

/**
 * Ophalen van het formulier
 * @param $form_id
 * @return false|string
 */
function getForm($form_id)
{
    $_GET["locatie"] = str_replace('_', ' ', (isset($_GET["locatie"])) ? esc_attr(urldecode($_GET["locatie"])) : null);

    ob_start();
    gravity_form_enqueue_scripts($form_id, true);
    gravity_form($form_id, false, true, false, "", true, 1);
    $c = ob_get_contents();
    ob_end_clean();
    return $c;
}

/**
 * Aanpassen van de bevestiging, bezette plaatsen verminderen.
 * @param $confirmation
 * @param $form
 * @param $lead
 * @param $ajax
 * @return mixed
 */
function custom_confirmation($confirmation, $form, $lead, $ajax)
{
    $field_key = 1;


    foreach ($form["fields"] as $key => $field) {
        if ($field["cssClass"] == "use_for_count") {
            $field_key = $field["id"];
            break;
        }
    }

    $post_id = (isset($_GET["post_id"])) ? esc_attr(urldecode($_GET["post_id"])) : null;
    $datum_van = (isset($_GET["datum_van"])) ? esc_attr(urldecode($_GET["datum_van"])) : null;
    $datum_tot = (isset($_GET["datum_tot"])) ? esc_attr(urldecode($_GET["datum_tot"])) : null;
    $locatie = (isset($_GET["locatie"])) ? esc_attr(urldecode($_GET["locatie"])) : null;

    $form_bezette_plaatsen = (int)$lead[$field_key];
    if (empty($form_bezette_plaatsen)) {
        $form_bezette_plaatsen = 1;
    }

    if (get_field("data", $post_id)) {
        while (has_sub_field("data", $post_id)) {
            if (!empty($acf_location = get_sub_field("location"))) {
                $acf_location = explode(";", $acf_location);
            }

            if (get_sub_field("datum_van") == $datum_van && get_sub_field("datum_tot") == $datum_tot && is_array($acf_location) && $acf_location[1] == $locatie) {
                $bezette_plaatsen = (int)get_sub_field("bezette_plaatsen");
                $new_bezette_plaatsen = (int)$bezette_plaatsen + (int)$form_bezette_plaatsen;
                update_sub_field("bezette_plaatsen", $new_bezette_plaatsen);
            }
        }
    }

    return $confirmation;
}

function get_subfilters() {
    $selected_filter = esc_attr( wp_strip_all_tags( $_POST['selected_filter'] ) );
    $main_term       = get_term_by( 'slug', $selected_filter, 'dlm_download_category' );
    $terms           = get_terms( [ 'taxonomy' => 'dlm_download_category', 'parent' => $main_term->term_id ] );

    if ( $terms ):
        ?>
        <div class="tree">
            <select onchange="jQuery('form#filterForm').submit();" name="subfilter" data-placeholder="Choose an option"
                    class="chosen-select-no-single">
                <option value=""><?php _t( "Kies een onderwerp binnen ‘%s’", $main_term->name ); ?></option>
                <?php foreach ( $terms as $term ): ?>
                    <option
                        value="<?php echo $term->slug ?>"<?php if ( $subfilter == $term->slug ): ?> selected="selected"<?php endif; ?>><?php echo $term->name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php
    endif;

    die();
}

// ----------------------------------------------------------------------------------------------------------------

add_shortcode('intramed_cursus_table', 'wpc_intramed_cursus_table');
add_shortcode('intramed_cursus_form', 'wpc_intramed_cursus_form');
add_shortcode('intramed_downloads', 'wpc_intramed_downloads');
wp_enqueue_style('cursusTable', plugin_dir_url(__FILE__) . 'assets/css/cursus.css');
wp_enqueue_style('downloads', plugin_dir_url(__FILE__) . 'assets/css/downloads.css');

$form_id = (isset($_GET["form_id"])) ? esc_attr(urldecode($_GET["form_id"])) : null;
add_filter("gform_confirmation_" . $form_id, "custom_confirmation", 3, 4);

add_action( 'wp_ajax_get_subfilters', 'get_subfilters' );
add_action( 'wp_ajax_nopriv_get_subfilters', 'get_subfilters' );