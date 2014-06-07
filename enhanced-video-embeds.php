<?php
/*
Plugin Name: Enhanced Video Embeds
Plugin URI: https://github.com/bjornjohansen/Enhanced-Video-Embeds-for-WP
Description: WordPress plugin for improved video embeds: Responsive and fast loading
Version: 0.1
Author: Bjørn Johansen
Author URI: https://bjornjohansen.no
Text Domain: enhanced-video-embeds
License: GPL2

	Copyright 2014 Bjørn Johansen  (email : post@bjornjohansen.no)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

new BJ_Enhanced_Video_Embeds;

class BJ_Enhanced_Video_Embeds {

	const version = '0.1';

	function __construct() {
		add_filter( 'oembed_dataparse', array( $this, 'maybe_modify_dataparse' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		
		add_filter( 'enhanced_video_embeds_oembed_html', array( $this, 'fix_autoplay_oembed_html' ), 10, 3 );
		add_filter( 'enhanced_video_embeds_videourl', array( $this, 'fix_autoplay_url' ), 10, 3 );
	}

	function enqueue_scripts_and_styles() {
		if ( defined( 'SCRIPT_DEBUG') && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'enhanced-video-embeds', plugins_url( '/js/enhanced-video-embeds.js', __FILE__ ), array( 'jquery' ), self::version, true );
		} else {
			wp_enqueue_script( 'enhanced-video-embeds', plugins_url( '/js/enhanced-video-embeds.min.js', __FILE__ ), array( 'jquery' ), self::version, true );
		}

		if ( apply_filters( 'enhanced_video_embeds_include_css', true ) ) {
			wp_enqueue_style( 'enhanced-video-embeds', plugins_url( '/css/enhanced-video-embeds.css', __FILE__ ), null, self::version );
		}

	}

	function maybe_modify_dataparse( $return, $data, $url ) {

		if ( 'video' == $data->type ) {
			$return = $this->apply( $return, $data, $url );
		}
		
		return $return;
	}

	function fix_autoplay_oembed_html( $oembed_result_html, $data, $videoURL ) {

		$oembed_result_html_orig = $oembed_result_html;

		$srcMatches = array();
		preg_match_all( '/src="(.*?)"/i', $oembed_result_html_orig, $srcMatches );
		$oembed_src_videoURL_orig = $srcMatches[1][0];
		$oembed_src_videoURL = add_query_arg( array( 'autoplay' => '1' ), $oembed_src_videoURL_orig );

		$oembed_result_html = str_replace( $oembed_src_videoURL_orig, $oembed_src_videoURL, $oembed_result_html );

		return $oembed_result_html;

	}

	function fix_autoplay_url( $videoURL, $data ) {

		$videoURL = add_query_arg( array( 'autoplay' => '1' ), $videoURL );

		return $videoURL;

	}

	public function apply( $return, $data, $url ) {

		$oembed_html = apply_filters( 'enhanced_video_embeds_oembed_html', $return, $data, $url );
		$url = apply_filters( 'enhanced_video_embeds_videourl', $url, $data);

		$wrapper_inline_css = array(
			'padding-top' => sprintf( '%.2f', $data->height / $data->width * 100 ) . '%' ,
			'background-image' => 'url(' . esc_url( $data->thumbnail_url ) . ')'
		);
		$wrapper_inline_css = apply_filters( 'enhanced_video_embeds_modifier_wrapper_inline_css', $wrapper_inline_css, $data, $url );

		$wrapper_attr = array(
			'style' => implode( '; ', array_map( 'bj_implode_css', $wrapper_inline_css, array_keys( $wrapper_inline_css ) ) ),
			'class' => 'EVE_Wrapper'
		);
		$wrapper_attr = apply_filters( 'enhanced_video_embeds_modifier_wrapper_attr', $wrapper_attr, $data, $url );

		$link_inline_css = array();
		$link_inline_css = apply_filters( 'enhanced_video_embeds_modifier_link_inline_css', $link_inline_css, $data, $url );

		$link_attr = array(
			'style' => implode( '; ', array_map( 'bj_implode_css', $link_inline_css, array_keys( $link_inline_css ) ) ),
			'href' => esc_url( $url ),
			'data-oembed-html' => $oembed_html,
			'title' => $data->title,
			'class' => 'EVE_Link'
		);
		$link_attr = apply_filters( 'enhanced_video_embeds_modifier_link_attr', $link_attr, $data, $url );

		$title_inline_css = array();
		$title_inline_css = apply_filters( 'enhanced_video_embeds_modifier_title_inline_css', $title_inline_css, $data, $url );

		$title_attr = array(
			'style' => implode( '; ', array_map( 'bj_implode_css', $title_inline_css, array_keys( $title_inline_css ) ) ),
			'class' => 'EVE_Title'
		);
		$title_attr = apply_filters( 'enhanced_video_embeds_modifier_title_attr', $title_attr, $data, $url );

		$title = apply_filters( 'enhanced_video_embeds_modifier_title', $data->title, $data, $url );

		$pre_wrapper = apply_filters( 'enhanced_video_embeds_modifier_pre_wrapper', '', $data, $url );
		$pre_link = apply_filters( 'enhanced_video_embeds_modifier_pre_link', '', $data, $url );
		$pre_title = apply_filters( 'enhanced_video_embeds_modifier_pre_title', '', $data, $url );
		$post_wrapper = apply_filters( 'enhanced_video_embeds_modifier_post_wrapper', '', $data, $url );
		$post_link = apply_filters( 'enhanced_video_embeds_modifier_post_link', '', $data, $url );
		$post_title = apply_filters( 'enhanced_video_embeds_modifier_post_title', '<span class="EVE_PlayBtn"><span class="EVE_PlayBtnLabel">' . __( 'Play', 'enhanced-video-embeds' ) . '</span></span>', $data, $url );

		$return = sprintf('%s<div %s>%s<a %s>%s<span %s>%s</span>%s</a>%s</div>%s',
			$pre_wrapper,
			implode( ' ', array_map( 'bj_implode_attr', $wrapper_attr, array_keys( $wrapper_attr ) ) ),
			$pre_link,
			implode( ' ', array_map( 'bj_implode_attr', $link_attr, array_keys( $link_attr ) ) ),
			$pre_title,
			implode( ' ', array_map( 'bj_implode_attr', $title_attr, array_keys( $title_attr ) ) ),
			esc_html( $title ),
			$post_title,
			$post_link,
			$post_wrapper
		);

		return $return;
	}

}


function bj_implode_css( $item, $key ) {
	return $key . ': ' . $item;
}

function bj_implode_attr( $item, $key ) {
	if ( is_array( $item ) ) {
		$item = implode( ' ', $item );
	}
	return sprintf( '%s="%s"', sanitize_key( $key ), esc_attr( $item ) );
}

