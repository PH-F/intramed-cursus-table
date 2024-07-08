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

// Shortcode to output custom PHP in Elementor
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
 * @param $form_id
 * @return false|string
 */
function getForm($form_id)
{
    ob_start();
    gravity_form_enqueue_scripts($form_id, true);
    gravity_form($form_id, false, true, false, "", true, 1);
    $c = ob_get_contents();
    ob_end_clean();
    return $c;
}

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
                $args = array();
                $args["post_id"] = get_the_ID();
                if (isset($form["id"])) {
                    $args["form_id"] = $form["id"];
                }

//            $args["datum_van"] = urlencode(get_sub_field("datum_van"));
//            $args["datum_tot"] = urlencode(get_sub_field("datum_tot"));
//            $args["locatie"] = urlencode($location[1]);
//            $args["tijd"] = urlencode(get_sub_field("tijd"));
//            $args["adresgegevens"] = urlencode(get_sub_field("adresgegevens"));
//            $args["event"] = urlencode(get_the_title(get_the_ID()));
//            if (!empty(get_sub_field("creatio_id"))) {
//                $args['creatio_id'] = sanitize_text_field(get_sub_field("creatio_id"));
//            }

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


add_shortcode('intramed_cursus_table', 'wpc_intramed_cursus_table');
add_shortcode('intramed_cursus_form', 'wpc_intramed_cursus_form');
wp_enqueue_style('cursusTable', plugin_dir_url(__FILE__) . 'assets/css/style.css');