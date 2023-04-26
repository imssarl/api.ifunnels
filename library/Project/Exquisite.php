<?php


/**
 * Работа с Popups.IO
 */
class Project_Exquisite{
	
	public static $version = '0001';
	public static $AWeberAppId = '1f2c492c';

	public static $arrFontAwesome = array('fa-glass', 'fa-music', 'fa-search', 'fa-envelope-o', 'fa-heart', 'fa-star', 'fa-star-o', 'fa-user', 'fa-film', 'fa-th-large', 'fa-th', 'fa-th-list', 'fa-check', 'fa-times', 'fa-search-plus', 'fa-search-minus', 'fa-power-off', 'fa-signal', 'fa-cog', 'fa-trash-o', 'fa-home', 'fa-file-o', 'fa-clock-o', 'fa-road', 'fa-download', 'fa-arrow-circle-o-down', 'fa-arrow-circle-o-up', 'fa-inbox', 'fa-play-circle-o', 'fa-repeat', 'fa-refresh', 'fa-list-alt', 'fa-lock', 'fa-flag', 'fa-headphones', 'fa-volume-off', 'fa-volume-down', 'fa-volume-up', 'fa-qrcode', 'fa-barcode', 'fa-tag', 'fa-tags', 'fa-book', 'fa-bookmark', 'fa-print', 'fa-camera', 'fa-font', 'fa-bold', 'fa-italic', 'fa-text-height', 'fa-text-width', 'fa-align-left', 'fa-align-center', 'fa-align-right', 'fa-align-justify', 'fa-list', 'fa-outdent', 'fa-indent', 'fa-video-camera', 'fa-picture-o', 'fa-pencil', 'fa-map-marker', 'fa-adjust', 'fa-tint', 'fa-pencil-square-o', 'fa-share-square-o', 'fa-check-square-o', 'fa-arrows', 'fa-step-backward', 'fa-fast-backward', 'fa-backward', 'fa-play', 'fa-pause', 'fa-stop', 'fa-forward', 'fa-fast-forward', 'fa-step-forward', 'fa-eject', 'fa-chevron-left', 'fa-chevron-right', 'fa-plus-circle', 'fa-minus-circle', 'fa-times-circle', 'fa-check-circle', 'fa-question-circle', 'fa-info-circle', 'fa-crosshairs', 'fa-times-circle-o', 'fa-check-circle-o', 'fa-ban', 'fa-arrow-left', 'fa-arrow-right', 'fa-arrow-up', 'fa-arrow-down', 'fa-share', 'fa-expand', 'fa-compress', 'fa-plus', 'fa-minus', 'fa-asterisk', 'fa-exclamation-circle', 'fa-gift', 'fa-leaf', 'fa-fire', 'fa-eye', 'fa-eye-slash', 'fa-exclamation-triangle', 'fa-plane', 'fa-calendar', 'fa-random', 'fa-comment', 'fa-magnet', 'fa-chevron-up', 'fa-chevron-down', 'fa-retweet', 'fa-shopping-cart', 'fa-folder', 'fa-folder-open', 'fa-arrows-v', 'fa-arrows-h', 'fa-bar-chart-o', 'fa-twitter-square', 'fa-facebook-square', 'fa-camera-retro', 'fa-key', 'fa-cogs', 'fa-comments', 'fa-thumbs-o-up', 'fa-thumbs-o-down', 'fa-star-half', 'fa-heart-o', 'fa-sign-out', 'fa-linkedin-square', 'fa-thumb-tack', 'fa-external-link', 'fa-sign-in', 'fa-trophy', 'fa-github-square', 'fa-upload', 'fa-lemon-o', 'fa-phone', 'fa-square-o', 'fa-bookmark-o', 'fa-phone-square', 'fa-twitter', 'fa-facebook', 'fa-github', 'fa-unlock', 'fa-credit-card', 'fa-rss', 'fa-hdd-o', 'fa-bullhorn', 'fa-bell', 'fa-certificate', 'fa-hand-o-right', 'fa-hand-o-left', 'fa-hand-o-up', 'fa-hand-o-down', 'fa-arrow-circle-left', 'fa-arrow-circle-right', 'fa-arrow-circle-up', 'fa-arrow-circle-down', 'fa-globe', 'fa-wrench', 'fa-tasks', 'fa-filter', 'fa-briefcase', 'fa-arrows-alt', 'fa-users', 'fa-link', 'fa-cloud', 'fa-flask', 'fa-scissors', 'fa-files-o', 'fa-paperclip', 'fa-floppy-o', 'fa-square', 'fa-bars', 'fa-list-ul', 'fa-list-ol', 'fa-strikethrough', 'fa-underline', 'fa-table', 'fa-magic', 'fa-truck', 'fa-pinterest', 'fa-pinterest-square', 'fa-google-plus-square', 'fa-google-plus', 'fa-money', 'fa-caret-down', 'fa-caret-up', 'fa-caret-left', 'fa-caret-right', 'fa-columns', 'fa-sort', 'fa-sort-asc', 'fa-sort-desc', 'fa-envelope', 'fa-linkedin', 'fa-undo', 'fa-gavel', 'fa-tachometer', 'fa-comment-o', 'fa-comments-o', 'fa-bolt', 'fa-sitemap', 'fa-umbrella', 'fa-clipboard', 'fa-lightbulb-o', 'fa-exchange', 'fa-cloud-download', 'fa-cloud-upload', 'fa-user-md', 'fa-stethoscope', 'fa-suitcase', 'fa-bell-o', 'fa-coffee', 'fa-cutlery', 'fa-file-text-o', 'fa-building-o', 'fa-hospital-o', 'fa-ambulance', 'fa-medkit', 'fa-fighter-jet', 'fa-beer', 'fa-h-square', 'fa-plus-square', 'fa-angle-double-left', 'fa-angle-double-right', 'fa-angle-double-up', 'fa-angle-double-down', 'fa-angle-left', 'fa-angle-right', 'fa-angle-up', 'fa-angle-down', 'fa-desktop', 'fa-laptop', 'fa-tablet', 'fa-mobile', 'fa-circle-o', 'fa-quote-left', 'fa-quote-right', 'fa-spinner', 'fa-circle', 'fa-reply', 'fa-github-alt', 'fa-folder-o', 'fa-folder-open-o', 'fa-smile-o', 'fa-frown-o', 'fa-meh-o', 'fa-gamepad', 'fa-keyboard-o', 'fa-flag-o', 'fa-flag-checkered', 'fa-terminal', 'fa-code', 'fa-reply-all', 'fa-mail-reply-all', 'fa-star-half-o', 'fa-location-arrow', 'fa-crop', 'fa-code-fork', 'fa-chain-broken', 'fa-question', 'fa-info', 'fa-exclamation', 'fa-superscript', 'fa-subscript', 'fa-eraser', 'fa-puzzle-piece', 'fa-microphone', 'fa-microphone-slash', 'fa-shield', 'fa-calendar-o', 'fa-fire-extinguisher', 'fa-rocket', 'fa-maxcdn', 'fa-chevron-circle-left', 'fa-chevron-circle-right', 'fa-chevron-circle-up', 'fa-chevron-circle-down', 'fa-html5', 'fa-css3', 'fa-anchor', 'fa-unlock-alt', 'fa-bullseye', 'fa-ellipsis-h', 'fa-ellipsis-v', 'fa-rss-square', 'fa-play-circle', 'fa-ticket', 'fa-minus-square', 'fa-minus-square-o', 'fa-level-up', 'fa-level-down', 'fa-check-square', 'fa-pencil-square', 'fa-external-link-square', 'fa-share-square', 'fa-compass', 'fa-caret-square-o-down', 'fa-caret-square-o-up', 'fa-caret-square-o-right', 'fa-eur', 'fa-gbp', 'fa-usd', 'fa-inr', 'fa-jpy', 'fa-rub', 'fa-krw', 'fa-btc', 'fa-file', 'fa-file-text', 'fa-sort-alpha-asc', 'fa-sort-alpha-desc', 'fa-sort-amount-asc', 'fa-sort-amount-desc', 'fa-sort-numeric-asc', 'fa-sort-numeric-desc', 'fa-thumbs-up', 'fa-thumbs-down', 'fa-youtube-square', 'fa-youtube', 'fa-xing', 'fa-xing-square', 'fa-youtube-play', 'fa-dropbox', 'fa-stack-overflow', 'fa-instagram', 'fa-flickr', 'fa-adn', 'fa-bitbucket', 'fa-bitbucket-square', 'fa-tumblr', 'fa-tumblr-square', 'fa-long-arrow-down', 'fa-long-arrow-up', 'fa-long-arrow-left', 'fa-long-arrow-right', 'fa-apple', 'fa-windows', 'fa-android', 'fa-linux', 'fa-dribbble', 'fa-skype', 'fa-foursquare', 'fa-trello', 'fa-female', 'fa-male', 'fa-gittip', 'fa-sun-o', 'fa-moon-o', 'fa-archive', 'fa-bug', 'fa-vk', 'fa-weibo', 'fa-renren', 'fa-pagelines', 'fa-stack-exchange', 'fa-arrow-circle-o-right', 'fa-arrow-circle-o-left', 'fa-caret-square-o-left', 'fa-dot-circle-o', 'fa-wheelchair', 'fa-vimeo-square', 'fa-try', 'fa-noicon');

	public function getData ($arrRequest=array()) {
		$jsonp_callback = $arrRequest['callback'];
		if (isset($arrRequest['action'])) {
			header( 'Content-Type: application/javascript' );
			switch ($arrRequest['action']) {
				case 'get-data':
					$layer_webfonts = array();
					$return_data = array();
					$style = '';//@file_get_contents(Zend_Registry::get( 'config' )->domain->url.Zend_Registry::get( 'config' )->path->html->user_files.'exquisite_popups/css/style.css');
					$front_header = '';
					$front_footer = '';
					if (isset($arrRequest['ulp'])){
						$str_id = explode(',', preg_replace('/[^a-zA-Z0-9,]/', '', $arrRequest['ulp']));//preg_replace('/[^a-zA-Z0-9]/', '', $arrRequest['ulp']);
					}else{
						$str_id = '0';
					}
					$_popup=new Project_Exquisite_Popups();
					$_url=parse_url( @$_SERVER['HTTP_REFERER'] );
					if( @$_url['host'] !== @$_SERVER['HTTP_HOST'] ){
						$_popup->noBlocked();
					}
					$popups=array();
					if( !empty( $str_id ) ){
						$_popup->onlyActive()->withStrIds( $str_id )->getList($popups);
					}
					$_flgFAEnabled='none';
					foreach ($popups as $popup) {
						if( $popup['user_id'] != 0 ){
							Zend_Registry::get( 'objUser' )->setById( $popup['user_id'] );
						}
						$_options=new Project_Exquisite_Options();
						$_options->get_options( $options );
						if ($options['fa_enable'] == 'on') {
							$_flgFAEnabled='on';
						}
						$popup_options = unserialize($popup['options']);
						$popup_options = array_merge(Project_Exquisite_Popups::$defaultOptions, $popup_options);
						$from = $this->get_rgb($popup_options['button_color']);
						$total = $from['r']+$from['g']+$from['b'];
						if ($total == 0) $total = 1;
						$to = array();
						$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
						$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
						$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
						$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
						$from_color = $popup_options['button_color'];
						if (!empty($popup_options['input_background_color'])) $bg_color = $this->get_rgb($popup_options['input_background_color']);
						if ($popup_options['button_gradient'] == 'on') {
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit,#ulp-'.$popup['str_id'].' .ulp-submit:visited{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');'.(!empty($popup_options['button_css']) ? $popup_options['button_css'] : '').'}';
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit:hover,#ulp-'.$popup['str_id'].' .ulp-submit:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');'.(!empty($popup_options['button_css_hover']) ? $popup_options['button_css_hover'] : '').'}';
						} else {
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit,#ulp-'.$popup['str_id'].' .ulp-submit:visited{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$from_color.';border:1px solid '.$from_color.';'.(!empty($popup_options['button_css']) ? $popup_options['button_css'] : '').'}';
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit:hover,#ulp-'.$popup['str_id'].' .ulp-submit:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$to_color.';border:1px solid '.$to_color.';'.(!empty($popup_options['button_css_hover']) ? $popup_options['button_css_hover'] : '').'}';
						}
						$style .= '#ulp-'.$popup['str_id'].' .ulp-input,#ulp-'.$popup['str_id'].' .ulp-input:hover,#ulp-'.$popup['str_id'].' .ulp-input:active,#ulp-'.$popup['str_id'].' .ulp-input:focus{border-width: '.intval($popup_options['input_border_width']).'px !important; border-radius: '.intval($popup_options['input_border_radius']).'px !important; border-color:'.(empty($popup_options['input_border_color']) ? 'transparent' : $popup_options['input_border_color']).';background-color:'.(empty($popup_options['input_background_color']) ? 'transparent' : $popup_options['input_background_color']).' !important;background-color:'.(empty($popup_options['input_background_color']) ? 'transparent' : 'rgba('.$bg_color['r'].','.$bg_color['g'].','.$bg_color['b'].','.floatval($popup_options['input_background_opacity'])).') !important;'.(!empty($popup_options['input_css']) ? $popup_options['input_css'] : '').'}';
						$style .= '#ulp-'.$popup['str_id'].'-overlay{background:'.(!empty($popup_options['overlay_color']) ? $popup_options['overlay_color'] : 'transparent').';opacity:'.$popup_options['overlay_opacity'].';-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=\''.intval(100*$popup_options['overlay_opacity']).'\')";filter:alpha(opacity="'.intval(100*$popup_options['overlay_opacity']).'");}';
						$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-button,#ulp-'.$popup['str_id'].' .ulp-submit-button:visited,#ulp-'.$popup['str_id'].' .ulp-submit-button:hover,#ulp-'.$popup['str_id'].' .ulp-submit-button:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important;}';
						if (!empty($popup_options['button_css'])) {
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-button,#ulp-'.$popup['str_id'].' .ulp-submit-button:visited{'.$popup_options['button_css'].'}';
						}
						if (!empty($popup_options['button_css_hover'])) {
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-button:hover,#ulp-'.$popup['str_id'].' .ulp-submit-button:active{'.$popup_options['button_css_hover'].'}';
						}
						$from = $this->get_rgb($popup_options['social2_facebook_color']);
						$total = $from['r']+$from['g']+$from['b'];
						if ($total == 0) $total = 1;
						$to = array();
						$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
						$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
						$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
						$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
						$from_color = $popup_options['social2_facebook_color'];
						if ($popup_options['button_gradient'] == 'on') {
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-facebook,#ulp-'.$popup['str_id'].' .ulp-submit-facebook:visited{background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');}';
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-facebook:hover,#ulp-'.$popup['str_id'].' .ulp-submit-facebook:active{background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');}';
						} else {
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-facebook,#ulp-'.$popup['str_id'].' .ulp-submit-facebook:visited{background: '.$from_color.';border:1px solid '.$from_color.';}';
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-facebook:hover,#ulp-'.$popup['str_id'].' .ulp-submit-facebook:active{background: '.$to_color.';border:1px solid '.$to_color.';}';
						}
						$from = $this->get_rgb($popup_options['social2_google_color']);
						$total = $from['r']+$from['g']+$from['b'];
						if ($total == 0) $total = 1;
						$to = array();
						$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
						$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
						$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
						$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
						$from_color = $popup_options['social2_google_color'];
						if ($popup_options['button_gradient'] == 'on') {
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-google,#ulp-'.$popup['str_id'].' .ulp-submit-google:visited{background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');}';
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-google:hover,#ulp-'.$popup['str_id'].' .ulp-submit-google:active{background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');}';
						} else {
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-google,#ulp-'.$popup['str_id'].' .ulp-submit-google:visited{background: '.$from_color.';border:1px solid '.$from_color.';}';
							$style .= '#ulp-'.$popup['str_id'].' .ulp-submit-google:hover,#ulp-'.$popup['str_id'].' .ulp-submit-google:active{background: '.$to_color.';border:1px solid '.$to_color.';}';
						}
						$front_footer .= '
							<div class="ulp-overlay" id="ulp-'.$popup['str_id'].'-overlay"></div>
							<div class="ulp-window" id="ulp-'.$popup['str_id'].'" data-title="'.htmlspecialchars($popup['title'], ENT_QUOTES).'" data-width="'.$popup_options['width'].'" data-height="'.$popup_options['height'].'" data-position="'.$popup_options['position'].'" data-close="'.$popup_options['enable_close'].'" data-cookie_value="'.$popup_options['cookie_value'].'" data-ga_tracking="'.$popup_options['ga_tracking'].'" data-google_clientid="'.$popup_options['google_clientid'].'" data-google_apikey="'.$popup_options['google_apikey'].'" data-facebook_appid="'.$popup_options['facebook_appid'].'" data-position="'.$popup_options['position'].'" >
								<div class="ulp-content">';
						$_layers=new Project_Exquisite_Layers();
						$_layers->withPopupId( $popup['id'] )->getList( $layers );
						foreach ($layers as $layer) {
							$layer_options = unserialize($layer['details']);
							if( empty( $layer_options ) ){
								$layer_options = unserialize( preg_replace_callback('!s:(\d+):"(.*?)";!s', "'s:'.strlen('$2').':\"$2\";'", $layer['details'] ) );
							}
							$layer_options = array_merge(Project_Exquisite_Layers::$defaultOptions, $layer_options);
							$layer_options = $this->filter_lp($layer_options, Project_Exquisite::urlBase());
							$content = str_replace(
								array('{subscription-name}', '{subscription-email}', '{subscription-phone}', '{subscription-message}', '{subscription-submit}'),
								array(
									'<input class="ulp-input" type="text" name="ulp-name" placeholder="'.htmlspecialchars($popup_options['name_placeholder'], ENT_QUOTES).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="fa fa-user"></i></div></div>' : ''),
									'<input class="ulp-input" type="text" name="ulp-email" placeholder="'.htmlspecialchars($popup_options['email_placeholder'], ENT_QUOTES).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="fa fa-envelope"></i></div></div>' : ''),
									'<input class="ulp-input" type="text" name="ulp-phone" placeholder="'.htmlspecialchars($popup_options['phone_placeholder'], ENT_QUOTES).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="fa fa-phone"></i></div></div>' : ''),
									'<textarea class="ulp-input" name="ulp-message" placeholder="'.htmlspecialchars($popup_options['message_placeholder'], ENT_QUOTES).'" onfocus="jQuery(this).removeClass(\'ulp-input-error\');"></textarea>',
									'<a class="ulp-submit'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe(this);"'.($options['fa_enable'] == 'on' && !empty($popup_options['button_icon']) && $popup_options['button_icon'] != 'fa-noicon' ? ' data-icon="'.$popup_options['button_icon'].'"' : '').' data-label="'.htmlspecialchars($popup_options['button_label'], ENT_QUOTES).'" data-loading="'.htmlspecialchars($popup_options['button_label_loading'], ENT_QUOTES).'">'.($options['fa_enable'] == 'on' && !empty($popup_options['button_icon']) && $popup_options['button_icon'] != 'fa-noicon' ? '<i class="fa '.$popup_options['button_icon'].'"></i>&nbsp; ' : '').htmlspecialchars($popup_options['button_label'], ENT_QUOTES).'</a>'),
								$layer_options['content']);
							$social_buttons = '';
							$social_facebook_like = '';
							$social_google_plusone = '';
							$social_twitter_tweet = '';
							$social_linkedin_share = '';
							if ($popup_options['social_google_plusone'] == 'on') {
								$return_data['social_google_plusone'] = 'on';
								$social_google_plusone = '<div class="ulp-social-button ulp-social-button-google-plusone" style="margin:0 '.intval($popup_options['social_margin']).'px;"><g:plusone size="tall" callback="ulp_social_google_plusone" href="'.$popup_options['social_url'].'"></g:plusone></div>';
								$social_buttons .= $social_google_plusone;
							}
							if ($popup_options['social_facebook_like'] == 'on') {
								$return_data['social_facebook_like'] = 'on';
								$social_facebook_like = '<div class="ulp-social-button ulp-social-button-facebook-like" style="margin:0 '.intval($popup_options['social_margin']).'px;"><fb:like id="fbLikeButton" href="'.$popup_options['social_url'].'" show_faces="false" layout="box_count"></fb:like></div>';
								$social_buttons .= $social_facebook_like;
							}
							if ($popup_options['social_twitter_tweet'] == 'on') {
								$return_data['social_twitter_tweet'] = 'on';
								$social_twitter_tweet = '<div class="ulp-social-button ulp-social-button-twitter-tweet" style="margin:0 '.intval($popup_options['social_margin']).'px;"><a href="http://twitter.com/share" class="twitter-share-button" data-text="" data-url="'.$popup_options['social_url'].'" data-count="vertical">Tweet</a></div>';
								$social_buttons .= $social_twitter_tweet;
							}
							if ($popup_options['social_linkedin_share'] == 'on') {
								$return_data['social_linkedin_share'] = 'on';
								$social_linkedin_share = '<div class="ulp-social-button ulp-social-button-linkedin-share" style="margin:0 '.intval($popup_options['social_margin']).'px;"><script type="IN/Share" data-url="'.$popup_options['social_url'].'" data-counter="top" data-onsuccess="ulp_social_linkedin_share"></script></div>';
								$social_buttons .= $social_linkedin_share;
							}
							$content = str_replace(
								array('{social-panel}', '{social-facebook-like}', '{social-google-plusone}', '{social-twitter-tweet}', '{social-linkedin-share}'),
								array($social_buttons, $social_facebook_like, $social_google_plusone, $social_twitter_tweet, $social_linkedin_share),
								$content);
							$facebook_button = '<a class="ulp-submit-button ulp-submit-facebook'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe_facebook(this);"'.($options['fa_enable'] == 'on' ? ' data-icon="fa-facebook"' : '').' data-label="'.htmlspecialchars($popup_options['social2_facebook_label'], ENT_QUOTES).'" data-loading="'.htmlspecialchars($popup_options['button_label_loading'], ENT_QUOTES).'">'.($options['fa_enable'] == 'on' ? '<i class="fa fa-facebook"></i>&nbsp; ' : '').htmlspecialchars($popup_options['social2_facebook_label'], ENT_QUOTES).'</a>';
							$google_button = '<a class="ulp-submit-button ulp-submit-google'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe_google(this);"'.($options['fa_enable'] == 'on' ? ' data-icon="fa-google"' : '').' data-label="'.htmlspecialchars($popup_options['social2_google_label'], ENT_QUOTES).'" data-loading="'.htmlspecialchars($popup_options['button_label_loading'], ENT_QUOTES).'">'.($options['fa_enable'] == 'on' ? '<i class="fa fa-google"></i>&nbsp; ' : '').htmlspecialchars($popup_options['social2_google_label'], ENT_QUOTES).'</a>';
							$content = str_replace(
								array('{subscription-facebook}', '{subscription-google}'),
								array($facebook_button, $google_button),
								$content);
							$base64 = false;
							if (strpos(strtolower($content), '<iframe') !== false || strpos(strtolower($content), '<video') !== false || strpos(strtolower($content), '<audio') !== false) {
								$base64 = true;
								$content = base64_encode($content);
							}
							$front_footer .= '
									<div class="ulp-layer" id="ulp-layer-'.$layer['id'].'" data-left="'.$layer_options['left'].'" data-top="'.$layer_options['top'].'" data-appearance="'.$layer_options['appearance'].'" data-appearance-speed="'.$layer_options['appearance_speed'].'" data-appearance-delay="'.$layer_options['appearance_delay'].'"'.(!empty($layer_options['width']) ? ' data-width="'.$layer_options['width'].'"' : '').(!empty($layer_options['height']) ? ' data-height="'.$layer_options['height'].'"' : '').' data-font-size="'.$layer_options['font_size'].'"'.($base64 ? ' data-base64="yes"' : '').(!empty($layer_options['scrollbar']) ? ' data-scrollbar="'.$layer_options['scrollbar'].'"' : ' data-scrollbar="off"').(!empty($layer_options['confirmation_layer']) ? ' data-confirmation="'.$layer_options['confirmation_layer'].'"' : ' data-confirmation="off"').'>'.$content.'</div>';
							if (!empty($layer_options['background_color'])) {
								$rgb = $this->get_rgb($layer_options['background_color']);
								$background = 'background-color:'.$layer_options['background_color'].';background-color:rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].');';
							} else $background = '';
							if (!empty($layer_options['background_image'])) {
								$background .= 'background-image:url('.$layer_options['background_image'].');background-repeat:repeat;';
							}
							$font = "font-family:'".$layer_options['font']."', arial;font-weight:".$layer_options['font_weight'].";color:".$layer_options['font_color'].";".($layer_options['text_shadow_size'] > 0 && !empty($layer_options['text_shadow_color']) ? "text-shadow: ".$layer_options['text_shadow_color']." ".$layer_options['text_shadow_size']."px ".$layer_options['text_shadow_size']."px ".$layer_options['text_shadow_size']."px;" : "");
							$style .= '#ulp-layer-'.$layer['id'].',#ulp-layer-'.$layer['id'].' p,#ulp-layer-'.$layer['id'].' a,#ulp-layer-'.$layer['id'].' span,#ulp-layer-'.$layer['id'].' li,#ulp-layer-'.$layer['id'].' input,#ulp-layer-'.$layer['id'].' button,#ulp-layer-'.$layer['id'].' textarea {'.$font.'}';
							if ($options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on') {
								$style .= '#ulp-layer-'.$layer['id'].' input.ulp-input {padding-left: '.intval(4+2*$layer_options['font_size']).'px !important;} #ulp-layer-'.$layer['id'].' div.ulp-fa-input-cell {width: '.intval(2*$layer_options['font_size']).'px !important; padding-left: 4px !important;}';
							}
							$style .= '#ulp-layer-'.$layer['id'].'{'.$background.'z-index:'.($layer_options['index']+1000002).';text-align:'.$layer_options['content_align'].';'.$layer_options['style'].'}';
							if (!array_key_exists($layer_options['font'], $local_fonts)) $layer_webfonts[] = $layer_options['font'];
						}
						$front_footer .= '
								</div>
							</div>';
					}
					if (!empty($layer_webfonts)) {
						$layer_webfonts = array_unique($layer_webfonts);
						include_once(dirname(__FILE__).'/webfonts.php');
						$webfonts_array = json_decode($fonts, true);
						$used_webfonts = array();
						foreach ($webfonts_array['items'] as $webfont) {
							if (in_array($webfont['family'], $layer_webfonts)) {
								$used_webfonts[] = $webfont;
							}
						}
						if(!empty($used_webfonts)){
							$i = 0;
							$families = array();
							$subsets = array();
							foreach($used_webfonts as $fontvars) {
								if (isset($fontvars['family']) && $fontvars['family']) {
									$words = explode(" ",$fontvars['family']);
									$families[$i] = implode('+', $words);
									if (isset($fontvars['variants']) && !empty($fontvars['variants'])) {
										foreach ($fontvars['variants'] as $key => $var) {
											if ($var == 'regular') $fontvars['variants'][$key] = '400';
											if ($var == 'italic') $fontvars['variants'][$key] = '400italic';
										}
										$families[$i] = $families[$i].":".implode(",", $fontvars['variants']);
									}
									if (isset($fontvars['subsets']) && !empty($fontvars['subsets'])) {
										foreach ($fontvars['subsets'] as $sub) {
											if(!in_array($sub, $subsets)){
												$subsets[] = $sub;
											}
										}
									}
								}
								$i++;
							}
							$query = '?family='.implode('|', $families);
							if (!empty($subsets)){
								$query .= '&subset='.implode(',', $subsets);
							}
							$front_header .= '<link href="http://fonts.googleapis.com/css'.$query.'" rel="stylesheet" type="text/css">';
						}
					}
					$front_header .= '<style>'.$style.'</style>';
					$front_header = $front_header;
					$return_data = array();
					$return_data['status'] = 'OK';
					$return_data['fa_enable'] = $_flgFAEnabled;
					$return_data['html'] = $front_header.$front_footer;
					$inline_ids = array();
					if (isset($arrRequest['inline_ids'])) {
						$inline_ids = explode(',', preg_replace('/[^a-zA-Z0-9,]/', '', $arrRequest['inline_ids']));
						if (sizeof($inline_ids) > 0) {
							foreach($inline_ids as $key => $value) {
								if (empty($value)) unset($inline_ids[$key]);
							}
						}
						if (sizeof($inline_ids) > 0) {
							if( @$_url['host'] !== @$_SERVER['HTTP_HOST'] ){
								$_popup->noBlocked();
							}
							$_popup->onlyActive()->withStrIds( $inline_ids )->getList($popups);
							foreach ($popups as $popup) {
								if( $popup['user_id'] != 0 ){
									Zend_Registry::get( 'objUser' )->setById( $popup['user_id'] );
								}
								$_options=new Project_Exquisite_Options();
								$_options->get_options( $options );
								$popup_options = unserialize($popup['options']);
								$popup_options = array_merge(Project_Exquisite_Popups::$defaultOptions, $popup_options);
								$style = '';
								$layer_webfonts = array();
								$from = $this->get_rgb($popup_options['button_color']);
								$total = $from['r']+$from['g']+$from['b'];
								if ($total == 0) $total = 1;
								$to = array();
								$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
								$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
								$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
								$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
								$from_color = $popup_options['button_color'];
								if (!empty($popup_options['input_background_color'])) $bg_color = $this->get_rgb($popup_options['input_background_color']);
								if ($popup_options['button_gradient'] == 'on') {
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit,#ulp-inline-'.$popup['str_id'].' .ulp-submit:visited{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');'.(!empty($popup_options['button_css']) ? $popup_options['button_css'] : '').'}';
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit:hover,#ulp-inline-'.$popup['str_id'].' .ulp-submit:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');'.(!empty($popup_options['button_css_hover']) ? $popup_options['button_css_hover'] : '').'}';
								} else {
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit,#ulp-inline-'.$popup['str_id'].' .ulp-submit:visited{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$from_color.';border:1px solid '.$from_color.';'.(!empty($popup_options['button_css']) ? $popup_options['button_css'] : '').'}';
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit:hover,#ulp-inline-'.$popup['str_id'].' .ulp-submit:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important; background: '.$to_color.';border:1px solid '.$to_color.';'.(!empty($popup_options['button_css_hover']) ? $popup_options['button_css_hover'] : '').'}';
								}
								$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-input,#ulp-inline-'.$popup['str_id'].' .ulp-input:hover,#ulp-inline-'.$popup['str_id'].' .ulp-input:active,#ulp-inline-'.$popup['str_id'].' .ulp-input:focus{border-width: '.intval($popup_options['input_border_width']).'px !important; border-radius: '.intval($popup_options['input_border_radius']).'px !important; border-color:'.(empty($popup_options['input_border_color']) ? 'transparent' : $popup_options['input_border_color']).';background-color:'.(empty($popup_options['input_background_color']) ? 'transparent' : $popup_options['input_background_color']).' !important;background-color:'.(empty($popup_options['input_background_color']) ? 'transparent' : 'rgba('.$bg_color['r'].','.$bg_color['g'].','.$bg_color['b'].','.floatval($popup_options['input_background_opacity'])).') !important;'.(!empty($popup_options['input_css']) ? $popup_options['input_css'] : '').'}';
								$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-button,#ulp-inline-'.$popup['str_id'].' .ulp-submit-button:visited,#ulp-inline-'.$popup['str_id'].' .ulp-submit-button:hover,#ulp-inline-'.$popup['str_id'].' .ulp-submit-button:active{border-radius: '.intval($popup_options['button_border_radius']).'px !important;}';
								if (!empty($popup_options['button_css'])) {
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-button,#ulp-inline-'.$popup['str_id'].' .ulp-submit-button:visited{'.$popup_options['button_css'].'}';
								}
								if (!empty($popup_options['button_css_hover'])) {
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-button:hover,#ulp-inline-'.$popup['str_id'].' .ulp-submit-button:active{'.$popup_options['button_css_hover'].'}';
								}
								$from = $this->get_rgb($popup_options['social2_facebook_color']);
								$total = $from['r']+$from['g']+$from['b'];
								if ($total == 0) $total = 1;
								$to = array();
								$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
								$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
								$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
								$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
								$from_color = $popup_options['social2_facebook_color'];
								if ($popup_options['button_gradient'] == 'on') {
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-facebook,#ulp-inline-'.$popup['str_id'].' .ulp-submit-facebook:visited{background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');}';
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-facebook:hover,#ulp-inline-'.$popup['str_id'].' .ulp-submit-facebook:active{background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');}';
								} else {
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-facebook,#ulp-inline-'.$popup['str_id'].' .ulp-submit-facebook:visited{background: '.$from_color.';border:1px solid '.$from_color.';}';
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-facebook:hover,#ulp-inline-'.$popup['str_id'].' .ulp-submit-facebook:active{background: '.$to_color.';border:1px solid '.$to_color.';}';
								}
								$from = $this->get_rgb($popup_options['social2_google_color']);
								$total = $from['r']+$from['g']+$from['b'];
								if ($total == 0) $total = 1;
								$to = array();
								$to['r'] = max(0, $from['r']-intval(48*$from['r']/$total));
								$to['g'] = max(0, $from['g']-intval(48*$from['g']/$total));
								$to['b'] = max(0, $from['b']-intval(48*$from['b']/$total));
								$to_color = '#'.($to['r'] < 16 ? '0' : '').dechex($to['r']).($to['g'] < 16 ? '0' : '').dechex($to['g']).($to['b'] < 16 ? '0' : '').dechex($to['b']);
								$from_color = $popup_options['social2_google_color'];
								if ($popup_options['button_gradient'] == 'on') {
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-google,#ulp-inline-'.$popup['str_id'].' .ulp-submit-google:visited{background: '.$from_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$to_color.','.$from_color.');}';
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-google:hover,#ulp-inline-'.$popup['str_id'].' .ulp-submit-google:active{background: '.$to_color.';border:1px solid '.$from_color.';background-image:linear-gradient('.$from_color.','.$to_color.');}';
								} else {
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-google,#ulp-inline-'.$popup['str_id'].' .ulp-submit-google:visited{background: '.$from_color.';border:1px solid '.$from_color.';}';
									$style .= '#ulp-inline-'.$popup['str_id'].' .ulp-submit-google:hover,#ulp-inline-'.$popup['str_id'].' .ulp-submit-google:active{background: '.$to_color.';border:1px solid '.$to_color.';}';
								}
								$html = '
									<div class="ulp-inline-window" id="ulp-inline-'.$popup['str_id'].'" data-title="'.htmlspecialchars($popup['title'], ENT_QUOTES).'" data-width="'.$popup_options['width'].'" data-height="'.$popup_options['height'].'" data-close="'.$popup_options['enable_close'].'">
										<div class="ulp-content">';
								$_layers=new Project_Exquisite_Layers();
								$_layers->withPopupId( $popup['id'] )->getList( $layers );
								foreach ($layers as $layer) {
									$layer_options = unserialize($layer['details']);
									if( empty( $layer_options ) ){
										$layer_options = unserialize( preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $layer['details'] ) );
									}
									$layer_options = array_merge(Project_Exquisite_Layers::$defaultOptions, $layer_options);
									$layer_options = $this->filter_lp($layer_options, Project_Exquisite::urlBase());
									if ($layer_options['inline_disable'] == 'on') continue;
									$content = str_replace(
										array('{subscription-name}', '{subscription-email}', '{subscription-phone}', '{subscription-message}', '{subscription-submit}'),
										array(
											'<input class="ulp-input" type="text" name="ulp-name" placeholder="'.htmlspecialchars($popup_options['name_placeholder'], ENT_QUOTES).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="fa fa-user"></i></div></div>' : ''),
											'<input class="ulp-input" type="text" name="ulp-email" placeholder="'.htmlspecialchars($popup_options['email_placeholder'], ENT_QUOTES).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="fa fa-envelope"></i></div></div>' : ''),
											'<input class="ulp-input" type="text" name="ulp-phone" placeholder="'.htmlspecialchars($popup_options['phone_placeholder'], ENT_QUOTES).'" value="" onfocus="jQuery(this).removeClass(\'ulp-input-error\');">'.($options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on' ? '<div class="ulp-fa-input-table"><div class="ulp-fa-input-cell"><i class="fa fa-phone"></i></div></div>' : ''),
											'<textarea class="ulp-input" name="ulp-message" placeholder="'.htmlspecialchars($popup_options['message_placeholder'], ENT_QUOTES).'" onfocus="jQuery(this).removeClass(\'ulp-input-error\');"></textarea>',
											'<a class="ulp-submit'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe(this);"'.($options['fa_enable'] == 'on' && !empty($popup_options['button_icon']) && $popup_options['button_icon'] != 'fa-noicon' ? ' data-icon="'.$popup_options['button_icon'].'"' : '').' data-label="'.htmlspecialchars($popup_options['button_label'], ENT_QUOTES).'" data-loading="'.htmlspecialchars($popup_options['button_label_loading'], ENT_QUOTES).'">'.($options['fa_enable'] == 'on' && !empty($popup_options['button_icon']) && $popup_options['button_icon'] != 'fa-noicon' ? '<i class="fa '.$popup_options['button_icon'].'"></i>&nbsp; ' : '').htmlspecialchars($popup_options['button_label'], ENT_QUOTES).'</a>'),
										$layer_options['content']);
									$social_buttons = '';
									$social_facebook_like = '';
									$social_google_plusone = '';
									$social_twitter_tweet = '';
									$social_linkedin_share = '';
									if ($popup_options['social_google_plusone'] == 'on') {
										$return_data['social_google_plusone'] = 'on';
										$social_google_plusone = '<div class="ulp-social-button ulp-social-button-google-plusone" style="margin:0 '.intval($popup_options['social_margin']).'px;"><g:plusone size="tall" callback="ulp_social_google_plusone" href="'.$popup_options['social_url'].'"></g:plusone></div>';
										$social_buttons .= $social_google_plusone;
									}
									if ($popup_options['social_facebook_like'] == 'on') {
										$return_data['social_facebook_like'] = 'on';
										$social_facebook_like = '<div class="ulp-social-button ulp-social-button-facebook-like" style="margin:0 '.intval($popup_options['social_margin']).'px;"><fb:like id="fbLikeButton" href="'.$popup_options['social_url'].'" show_faces="false" layout="box_count"></fb:like></div>';
										$social_buttons .= $social_facebook_like;
									}
									if ($popup_options['social_twitter_tweet'] == 'on') {
										$return_data['social_twitter_tweet'] = 'on';
										$social_twitter_tweet = '<div class="ulp-social-button ulp-social-button-twitter-tweet" style="margin:0 '.intval($popup_options['social_margin']).'px;"><a href="http://twitter.com/share" class="twitter-share-button" data-text="" data-url="'.$popup_options['social_url'].'" data-count="vertical">Tweet</a></div>';
										$social_buttons .= $social_twitter_tweet;
									}
									if ($popup_options['social_linkedin_share'] == 'on') {
										$return_data['social_linkedin_share'] = 'on';
										$social_linkedin_share = '<div class="ulp-social-button ulp-social-button-linkedin-share" style="margin:0 '.intval($popup_options['social_margin']).'px;"><script type="IN/Share" data-url="'.$popup_options['social_url'].'" data-counter="top" data-onsuccess="ulp_social_linkedin_share"></script></div>';
										$social_buttons .= $social_linkedin_share;
									}
									$content = str_replace(
										array('{social-panel}', '{social-facebook-like}', '{social-google-plusone}', '{social-twitter-tweet}', '{social-linkedin-share}'),
										array($social_buttons, $social_facebook_like, $social_google_plusone, $social_twitter_tweet, $social_linkedin_share),
										$content);
									$facebook_button = '<a class="ulp-submit-button ulp-submit-facebook'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe_facebook(this);"'.($options['fa_enable'] == 'on' ? ' data-icon="fa-facebook"' : '').' data-label="'.htmlspecialchars($popup_options['social2_facebook_label'], ENT_QUOTES).'" data-loading="'.htmlspecialchars($popup_options['button_label_loading'], ENT_QUOTES).'">'.($options['fa_enable'] == 'on' ? '<i class="fa fa-facebook"></i>&nbsp; ' : '').htmlspecialchars($popup_options['social2_facebook_label'], ENT_QUOTES).'</a>';
									$google_button = '<a class="ulp-submit-button ulp-submit-google'.($popup_options['button_inherit_size'] == 'on' ? ' ulp-inherited' : '').'" onclick="return ulp_subscribe_google(this);"'.($options['fa_enable'] == 'on' ? ' data-icon="fa-google"' : '').' data-label="'.htmlspecialchars($popup_options['social2_google_label'], ENT_QUOTES).'" data-loading="'.htmlspecialchars($popup_options['button_label_loading'], ENT_QUOTES).'">'.($options['fa_enable'] == 'on' ? '<i class="fa fa-google"></i>&nbsp; ' : '').htmlspecialchars($popup_options['social2_google_label'], ENT_QUOTES).'</a>';
									$content = str_replace(
										array('{subscription-facebook}', '{subscription-google}'),
										array($facebook_button, $google_button),
										$content);
									$base64 = false;
									if (strpos(strtolower($content), '<iframe') !== false || strpos(strtolower($content), '<video') !== false || strpos(strtolower($content), '<audio') !== false) {
										$base64 = true;
										$content = base64_encode($content);
									}
									$html .= '
											<div class="ulp-layer" id="ulp-inline-layer-'.$layer['id'].'" data-left="'.$layer_options['left'].'" data-top="'.$layer_options['top'].'" data-appearance="'.$layer_options['appearance'].'" data-appearance-speed="'.$layer_options['appearance_speed'].'" data-appearance-delay="'.$layer_options['appearance_delay'].'"'.(!empty($layer_options['width']) ? ' data-width="'.$layer_options['width'].'"' : '').(!empty($layer_options['height']) ? ' data-height="'.$layer_options['height'].'"' : '').' data-font-size="'.$layer_options['font_size'].'"'.($base64 ? ' data-base64="yes"' : '').(!empty($layer_options['confirmation_layer']) ? ' data-confirmation="'.$layer_options['confirmation_layer'].'"' : ' data-confirmation="off"').'>'.$content.'</div>';
									if (!empty($layer_options['background_color'])) {
										$rgb = $this->get_rgb($layer_options['background_color']);
										$background = 'background-color:'.$layer_options['background_color'].';background-color:rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$layer_options['background_opacity'].');';
									} else $background = '';
									if (!empty($layer_options['background_image'])) {
										$background .= 'background-image:url('.$layer_options['background_image'].');background-repeat:repeat;';
									}
									$font = "font-family:'".$layer_options['font']."', arial;font-weight:".$layer_options['font_weight'].";color:".$layer_options['font_color'].";".($layer_options['text_shadow_size'] > 0 && !empty($layer_options['text_shadow_color']) ? "text-shadow: ".$layer_options['text_shadow_color']." ".$layer_options['text_shadow_size']."px ".$layer_options['text_shadow_size']."px ".$layer_options['text_shadow_size']."px;" : "");
									$style .= '#ulp-inline-layer-'.$layer['id'].',#ulp-inline-layer-'.$layer['id'].' p,#ulp-inline-layer-'.$layer['id'].' a,#ulp-inline-layer-'.$layer['id'].' span,#ulp-inline-layer-'.$layer['id'].' li,#ulp-inline-layer-'.$layer['id'].' input,#ulp-inline-layer-'.$layer['id'].' button,#ulp-inline-layer-'.$layer['id'].' textarea {'.$font.'}';
									if ($options['fa_enable'] == 'on' && $popup_options['input_icons'] == 'on') {
										$style .= '#ulp-inline-layer-'.$layer['id'].' input.ulp-input {padding-left: '.intval(4+2*$layer_options['font_size']).'px !important;} #ulp-inline-layer-'.$layer['id'].' div.ulp-fa-input-cell {width: '.intval(2*$layer_options['font_size']).'px !important; padding-left: 4px !important;}';
									}
									$style .= '#ulp-inline-layer-'.$layer['id'].'{'.$background.'z-index:'.($layer_options['index']+10).';text-align:'.$layer_options['content_align'].';'.$layer_options['style'].'}';
									if (!array_key_exists($layer_options['font'], $local_fonts)) $layer_webfonts[] = $layer_options['font'];
								}
								$html .= '
										</div>
									</div>';
								$html = '<style>'.$style.'</style>'.$html;
								if (!empty($layer_webfonts)) {
									$layer_webfonts = array_unique($layer_webfonts);
									include_once(dirname(__FILE__).'/webfonts.php');
									$webfonts_array = json_decode($fonts, true);
									$used_webfonts = array();
									foreach ($webfonts_array['items'] as $webfont) {
										if (in_array($webfont['family'], $layer_webfonts)) {
											$used_webfonts[] = $webfont;
										}
									}
									if(!empty($used_webfonts)){
										$i = 0;
										$families = array();
										$subsets = array();
										foreach($used_webfonts as $fontvars) {
											if (isset($fontvars['family']) && $fontvars['family']) {
												$words = explode(" ",$fontvars['family']);
												$families[$i] = implode('+', $words);
												if (isset($fontvars['variants']) && !empty($fontvars['variants'])) {
													foreach ($fontvars['variants'] as $key => $var) {
														if ($var == 'regular') $fontvars['variants'][$key] = '400';
														if ($var == 'italic') $fontvars['variants'][$key] = '400italic';
													}
													$families[$i] = $families[$i].":".implode(",", $fontvars['variants']);
												}
												if (isset($fontvars['subsets']) && !empty($fontvars['subsets'])) {
													foreach ($fontvars['subsets'] as $sub) {
														if(!in_array($sub, $subsets)){
															$subsets[] = $sub;
														}
													}
												}
											}
											$i++;
										}
										$query = '?family='.implode('|', $families);
										if (!empty($subsets)){
											$query .= '&subset='.implode(',', $subsets);
										}
										$html = '<link href="http://fonts.googleapis.com/css'.$query.'" rel="stylesheet" type="text/css">'.$html;
									}
								}
								$return_data['inline_popups'][$popup['str_id']] = $html;
							}
						}
					}
					echo $jsonp_callback.'('.json_encode($return_data).')';
					exit;
					break;
				case 'subscribe':
					if (isset($arrRequest['name'])) $name = base64_decode(trim(stripslashes($arrRequest['name'])));
					else $name = '';
					if (isset($arrRequest['email'])) $email = base64_decode(trim(stripslashes($arrRequest['email'])));
					else $email = '';
					if (isset($arrRequest['phone'])) $phone = base64_decode(trim(stripslashes($arrRequest['phone'])));
					else $phone = '';
					if (isset($arrRequest['message'])) $message = base64_decode(trim(stripslashes($arrRequest['message'])));
					else $message = '';
					if (isset($arrRequest['ulp'])) $str_id = trim(stripslashes($arrRequest['ulp']));
					else {
						$return_data = array();
						$return_data['status'] = 'FATAL';
						echo $jsonp_callback.'('.json_encode($return_data).')';
						exit;
					}
					$str_id = preg_replace('/[^a-zA-Z0-9]/', '', $str_id);
					// ADD LPB TEST
					if( $str_id == 'mooptin' ){
						$arrRequest['mooptinid']=trim(stripslashes($arrRequest['mooptinid']));
						Project_Mooptin_Autoresponders::sendAutorespond( $arrRequest );
						$_subscriber=new Project_Squeeze_Subscribers(trim(stripslashes($arrRequest['userid'])));
						$_subscriber->setEntered( array(
							'squeeze_id'=>trim(stripslashes($arrRequest['lpbid'])),
							'user_id'=>trim(stripslashes($arrRequest['userid'])),
							'name'=>$name,
							'email'=>$email,
							'phone'=>$phone,
							'message'=>$message,
						) );
						$_subscriber->set();
						//======================================================================= TODO
						$return_data = array();
						$return_data['status'] = 'OK';
						$return_data['return_url'] = '';//$popup_options['return_url'];
						$return_data['close_delay'] = 1000;//1000*intval($popup_options['close_delay']);
						echo $jsonp_callback.'('.json_encode($return_data).')';
						//=======================================================================
						exit;
					}else{
						$_popup=new Project_Exquisite_Popups();
						$_popup->withStrIds( $str_id )->get($popup_details);
						if (empty($popup_details)) {
							$return_data = array();
							$return_data['status'] = 'FATAL';
							echo $jsonp_callback.'('.json_encode($return_data).')';
							exit;
						}
					}
					if( $popup['user_id'] != 0 ){
						Zend_Registry::get( 'objUser' )->setById( $popup_details['user_id'] );
					}
					$_options=new Project_Exquisite_Options();
					$_options->get_options( $options );
					$return_data = array();
					if ($email == '' || !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/i", $email)) $return_data['email'] = 'ERROR';
					else {
						if ($options['email_validation'] == 'on') {
							$email_parts = explode('@',$email);
							if(checkdnsrr($email_parts[1], 'MX')) {
							} else $return_data['email'] = 'ERROR';
						}
					}
					$popup_options = unserialize($popup_details['options']);
					$popup_options = array_merge(Project_Exquisite_Popups::$defaultOptions, $popup_options);
					if ($popup_options['name_mandatory'] == 'on' && empty($name)) $return_data['name'] = 'ERROR';
					if ($popup_options['phone_mandatory'] == 'on' && empty($phone)) $return_data['phone'] = 'ERROR';
					if ($popup_options['message_mandatory'] == 'on' && empty($message)) $return_data['message'] = 'ERROR';
					if (!empty($return_data)) {
						$return_data['status'] = 'ERROR';
						echo $jsonp_callback.'('.json_encode($return_data).')';
						exit;
					}
					$_subscriber=new Project_Exquisite_Subscribers();
					$_subscriber->setEntered( array(
						'popup_id'=>$popup_details['id'],
						'name'=>$name,
						'email'=>$email,
						'phone'=>$phone,
						'message'=>$message,
					) );
					$_subscriber->set();
					if (empty($name)) $name = substr($email, 0, strpos($email, '@'));
					if ($popup_options['mailchimp_enable'] == 'on') {
						$list_id = $popup_options['mailchimp_list_id'];
						$dc = "us1";
						if (strstr($popup_options['mailchimp_api_key'], "-")) {
							list($key, $dc) = explode("-", $popup_options['mailchimp_api_key'], 2);
							if (!$dc) $dc = "us1";
						}
						$mailchimp_url = 'http://'.$dc.'.api.mailchimp.com/1.3/?method=listSubscribe&apikey='.$popup_options['mailchimp_api_key'].'&id='.$list_id.'&email_address='.urlencode($email).'&merge_vars[FNAME]='.urlencode($name).'&merge_vars[LNAME]='.urlencode($name).'&merge_vars[NAME]='.urlencode($name).(!empty($phone) ? '&merge_vars[PHONE]='.urlencode($phone) : '').'&merge_vars[OPTIN_IP]='.$_SERVER['REMOTE_ADDR'].'&output=php&double_optin='.($popup_options['mailchimp_double'] == 'on' ? '1' : '0').'&send_welcome='.($popup_options['mailchimp_welcome'] == 'on' ? '1' : '0');
						$ch = curl_init($mailchimp_url);
						curl_setopt($ch, CURLOPT_URL, $mailchimp_url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_ENCODING, "");
						curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent: MCAPI/1.3');
						curl_setopt($ch, CURLOPT_TIMEOUT, 30);
						curl_setopt($ch, CURLOPT_FAILONERROR, 1);
						curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, null);
						$data = curl_exec( $ch );
						curl_close( $ch );
					}
					if ($popup_options['icontact_enable'] == 'on') {
						$this->icontact_addcontact($popup_options['icontact_appid'], $popup_options['icontact_apiusername'], $popup_options['icontact_apipassword'], $popup_options['icontact_listid'], $name, $email);
					}
					if ($popup_options['campaignmonitor_enable'] == 'on') {
						$options['EmailAddress'] = $email;
						$options['Name'] = $name;
						$options['Resubscribe'] = 'true';
						$options['RestartSubscriptionBasedAutoresponders'] = 'true';
						$post = json_encode($options);
						$curl = curl_init('https://api.createsend.com/api/v3/subscribers/'.urlencode($popup_options['campaignmonitor_list_id']).'.json');
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
						$header = array(
							'Content-Type: application/json',
							'Content-Length: '.strlen($post),
							'Authorization: Basic '.base64_encode($popup_options['campaignmonitor_api_key'])
							);
						curl_setopt($curl, CURLOPT_PORT, 443);
						curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
						curl_setopt($curl, CURLOPT_TIMEOUT, 10);
						curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // verify certificate
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // check existence of CN and verify that it matches hostname
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
						curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
						$response = curl_exec($curl);
						curl_close($curl);
					}
					if ($popup_options['getresponse_enable'] == 'on') {
						$request = json_encode(
							array(
								'method' => 'add_contact',
								'params' => array(
									$popup_options['getresponse_api_key'],
									array(
										'campaign' => $popup_options['getresponse_campaign_id'],
										'action' => 'standard',
										'name' => $name,
										'email' => $email,
										'ip' => $_SERVER['REMOTE_ADDR']
									)
								),
								'id' => ''
							)
						);
						$curl = curl_init('https://api2.getresponse.com/');
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
						$header = array(
							'Content-Type: application/json',
							'Content-Length: '.strlen($request)
						);
						curl_setopt($curl, CURLOPT_PORT, 443);
						curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
						curl_setopt($curl, CURLOPT_TIMEOUT, 10);
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // verify certificate
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // check existence of CN and verify that it matches hostname
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
						curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
						curl_setopt($curl, CURLOPT_HEADER, 0);
						$response = curl_exec($curl);
						curl_close($curl);
					}
					if ($options['aweber_access_secret']) {
						if ($popup_options['aweber_enable'] == 'on') {
							$account = null;
							if (!class_exists('AWeberAPI')) {
								require_once(dirname(__FILE__).'/aweber_api/aweber_api.php');
							}
							try {
								$aweber = new AWeberAPI($options['aweber_consumer_key'], $options['aweber_consumer_secret']);
								$account = $aweber->getAccount($options['aweber_access_key'], $options['aweber_access_secret']);
								$subscribers = $account->loadFromUrl('/accounts/' . $account->id . '/lists/' . $popup_options['aweber_listid'] . '/subscribers');
								$subscribers->create(array(
									'email' => $email,
									'ip_address' => $_SERVER['REMOTE_ADDR'],
									'name' => $name,
									'ad_tracking' => 'Layered Popups',
								));
							} catch (Exception $e) {
								$account = null;
							}
						}
					}
					if ($popup_options['madmimi_enable'] == 'on') {
						$request = http_build_query(array(
							'email' => $email,
							'first_name' => $name,
							'last_name' => '',
							'username' => $popup_options['madmimi_login'],
							'api_key' => $popup_options['madmimi_api_key']
						));
						$curl = curl_init('http://api.madmimi.com/audience_lists/'.$popup_options['madmimi_list_id'].'/add');
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
						curl_setopt($curl, CURLOPT_TIMEOUT, 20);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
						curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
						curl_setopt($curl, CURLOPT_HEADER, 0);
						$response = curl_exec($curl);
						curl_close($curl);
					}
					if ($popup_options['sendy_enable'] == 'on') {
						$request = http_build_query(array(
							'email' => $email,
							'name' => $name,
							'list' => $popup_options['sendy_listid'],
							'boolean' => 'true'
						));
						$options['sendy_url'] = rtrim($options['sendy_url'], '/');
						$curl = curl_init($options['sendy_url'].'/subscribe');
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
						curl_setopt($curl, CURLOPT_TIMEOUT, 20);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
						curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
						curl_setopt($curl, CURLOPT_HEADER, 0);
						$response = curl_exec($curl);
						curl_close($curl);
					}
					if ($popup_options['benchmark_enable'] == 'on') {
						$request = http_build_query(array(
							'contacts' => array(
								'email' => $email,
								'firstname' => $name,
								'lastname' => ''),
							'optin' => ($popup_options['benchmark_double'] == 'on' ? 1 : 0),
							'listID' => $popup_options['benchmark_list_id'],
							'token' => $options['benchmark_api_key']
						));

						$curl = curl_init('http://www.benchmarkemail.com/api/1.0/?output=php&method=listAddContacts');
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
						curl_setopt($curl, CURLOPT_TIMEOUT, 20);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
						curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
						curl_setopt($curl, CURLOPT_HEADER, 0);
						$response = curl_exec($curl);
						curl_close($curl);
					}
					if ($popup_options['activecampaign_enable'] == 'on') {
						$request = http_build_query(array(
							'api_action' => 'contact_add',
							'api_key' => $options['activecampaign_api_key'],
							'api_output' => 'serialize',
							'p['.$popup_options['activecampaign_list_id'].']' => $popup_options['activecampaign_list_id'],
							'email' => $email,
							'first_name' => $name,
							'ip4' => $_SERVER['REMOTE_ADDR']
						));

						$url = str_replace('https://', 'http://', $options['activecampaign_url']);
						$curl = curl_init($url.'/admin/api.php?api_action=contact_add');
						curl_setopt($curl, CURLOPT_POST, 1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
						curl_setopt($curl, CURLOPT_TIMEOUT, 20);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
						curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
						curl_setopt($curl, CURLOPT_HEADER, 0);
						$response = curl_exec($curl);
						curl_close($curl);
					}
					if ($popup_options['interspire_enable'] == 'on') {
						try {
							$xml = '
<xmlrequest>
	<username>'.$options['interspire_username'].'</username>
	<usertoken>'.$options['interspire_token'].'</usertoken>
	<requesttype>subscribers</requesttype>
	<requestmethod>AddSubscriberToList</requestmethod>
	<details>
		<emailaddress>'.$email.'</emailaddress>
		<mailinglist>'.$popup_options['interspire_listid'].'</mailinglist>
		<format>html</format>
		<confirmed>yes</confirmed>';
					if (!empty($popup_options['interspire_nameid'])) {
						$xml .= '
		<customfields>;
			<item>
				<fieldid>'.$popup_options['interspire_nameid'].'</fieldid>
				<value>'.$name.'</value>
			</item>
		</customfields>';
					}
					$xml .= '
	</details>
</xmlrequest>';
							$curl = curl_init($options['interspire_url']);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
							curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt($curl, CURLOPT_HEADER, 0);
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
							$response = curl_exec($curl);
							curl_close($curl);
						} catch (Exception $e) {
						}
					}
					$return_data = array();
					$return_data['status'] = 'OK';
					$return_data['return_url'] = $popup_options['return_url'];
					$return_data['close_delay'] = 1000*intval($popup_options['close_delay']);
					echo $jsonp_callback.'('.json_encode($return_data).')';
					exit;
					break;
				case 'share':
					if (isset($arrRequest['ulp'])) $str_id = trim(stripslashes($arrRequest['ulp']));
					else {
						$return_data = array();
						$return_data['status'] = 'FATAL';
						echo $jsonp_callback.'('.json_encode($return_data).')';
						exit;
					}
					$str_id = preg_replace('/[^a-zA-Z0-9]/', '', $str_id);
					$_popup=new Project_Exquisite_Popups();
					$_popup->withStrIds( $str_id )->get($popup_details);
					if (empty($popup_details)) {
						$return_data = array();
						$return_data['status'] = 'FATAL';
						echo $jsonp_callback.'('.json_encode($return_data).')';
						exit;
					}
					$return_data = array();
					$popup_options = unserialize($popup_details['options']);
					$popup_options = array_merge(Project_Exquisite_Popups::$defaultOptions, $popup_options);
					if (!empty($return_data)) {
						$return_data['status'] = 'ERROR';
						echo $jsonp_callback.'('.json_encode($return_data).')';
						exit;
					}
					$return_data = array();
					$return_data['status'] = 'OK';
					$return_data['return_url'] = $popup_options['return_url'];
					$return_data['close_delay'] = 1000*intval($popup_options['close_delay']);
					echo $jsonp_callback.'('.json_encode($return_data).')';
					exit;
					break;

				default:
					break;
			}
		}
	}
	
	public static function getOnLoadCampaign( $id, &$script, $settings=array() ){
		$script='<script type="text/javascript" src="//'.Zend_Registry::get( 'config' )->domain->host.'/usersdata/exquisite_popups/js/ulp-jsonp.js" class="exquisite_popup" data-id="'.base64_encode( http_build_query( $settings+array(
			'id'=>$id,
			'display_mode'=>'onload',
			'load_mode'=>'every-time',
			'onload_delay'=>'0',
		) ) ).'"></script>';
	}
	
	public static function getOnActionCampaign( $id, &$script, &$action ){
		$script='<script type="text/javascript" src="//'.Zend_Registry::get( 'config' )->domain->host.'/usersdata/exquisite_popups/js/ulp-jsonp.js" class="exquisite_popup" data-id="'.base64_encode( http_build_query( array(
			'id'=>$id,
			'display_mode'=>'onaction',
		) ) ).'"></script>';
		$action='ulp_open(\''.$id.'\');';
	}
	
	public static function urlBase() {
		return Zend_Registry::get( 'config' )->domain->url.Zend_Registry::get( 'config' )->path->html->user_files.'exquisite_popups';
	}

	public function filter_lp($_layer_options, $_url_base) {
		foreach ($_layer_options as $key => $value) {
			$_layer_options[$key]=str_replace('ULP-DEMO-IMAGES-URL', $_url_base, $value);
		}
		return $_layer_options;
	}

	public function filter_lp_reverse($_layer_options, $_url_base) {
		foreach ($_layer_options as $key => $value) {
			$_layer_options[$key]=str_replace($_url_base, 'ULP-DEMO-IMAGES-URL', $value);
		}
		return $_layer_options;
	}

	public function page_switcher ($_urlbase, $_currentpage, $_totalpages) {
		$pageswitcher="";
		if ($_totalpages > 1) {
			$pageswitcher='<div class="tablenav bottom"><div class="tablenav-pages">Pages: <span class="pagiation-links">';
			if (strpos($_urlbase,"?") !== false) $_urlbase .= "&amp;";
			else $_urlbase .= "?";
			if ($_currentpage == 1) $pageswitcher .= "<strong>1</strong> ";
			else $pageswitcher .= " <a class='page' href='".$_urlbase."p=1'>1</a> ";

			$start=max($_currentpage-3, 2);
			$end=min(max($_currentpage+3,$start+6), $_totalpages-1);
			$start=max(min($start,$end-6), 2);
			if ($start > 2) $pageswitcher .= " <b>...</b> ";
			for ($i=$start; $i<=$end; $i++) {
				if ($_currentpage == $i) $pageswitcher .= " <strong>".$i."</strong> ";
				else $pageswitcher .= " <a class='page' href='".$_urlbase."p=".$i."'>".$i."</a> ";
			}
			if ($end < $_totalpages-1) $pageswitcher .= " <b>...</b> ";

			if ($_currentpage == $_totalpages) $pageswitcher .= " <strong>".$_totalpages."</strong> ";
			else $pageswitcher .= " <a class='page' href='".$_urlbase."p=".$_totalpages."'>".$_totalpages."</a> ";
			$pageswitcher .= "</span></div></div>";
		}
		return $pageswitcher;
	}

	public function get_rgb($_color) {
		if (strlen($_color) != 7 && strlen($_color) != 4) return false;
		$color=preg_replace('/[^#a-fA-F0-9]/', '', $_color);
		if (strlen($color) != strlen($_color)) return false;
		if (strlen($color) == 7) list($r, $g, $b)=array($color[1].$color[2], $color[3].$color[4], $color[5].$color[6]);
		else list($r, $g, $b)=array($color[1].$color[1], $color[2].$color[2], $color[3].$color[3]);
		return array("r" => hexdec($r), "g" => hexdec($g), "b" => hexdec($b));
	}

	public function random_string($_length=16) {
		$symbols='123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string="";
		for ($i=0; $i<$_length; $i++) {
			$string .= $symbols[rand(0, strlen($symbols)-1)];
		}
		return $string;
	}

	public function icontact_addcontact($appid, $apiusername, $apipassword, $listid, $name, $email) {
		$_options=new Project_Exquisite_Options();
		$_options->get_options( $options );
		$_object=new Project_Exquisite();
		$data=$_object->icontact_makecall($appid, $apiusername, $apipassword, '/a/', null, 'accounts');
		if (!empty($data['errors'])) return;
		$account=$data['response'][0];
		if (empty($account) || intval($account->enabled != 1)) return;
		$data=$_object->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/', null, 'clientfolders');
		if (!empty($data['errors'])) return;
		$client=$data['response'][0];
		if (empty($client)) return;
		$contact['email']=$email;
		if ($options['disable_name'] != 'on') $contact['firstName']=$name;
		$contact['status']='normal';
		$data=$_object->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/'.$client->clientFolderId.'/contacts', array($contact), 'contacts');
		if (!empty($data['errors'])) return;
		$contact=$data['response'][0];
		if (empty($contact)) return;
		$subscriber['contactId']=$contact->contactId;
		$subscriber['listId']=$listid;
		$subscriber['status']='normal';
		$data=$_object->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/'.$client->clientFolderId.'/subscriptions', array($subscriber), 'subscriptions');
	}

	public function icontact_getlists($appid, $apiusername, $apipassword) {
		$_object=new Project_Exquisite();
		$data=$_object->icontact_makecall($appid, $apiusername, $apipassword, '/a/', null, 'accounts');
		if (!empty($data['errors'])) return array();
		$account=$data['response'][0];
		if (empty($account) || intval($account->enabled != 1)) return;
		$data=$_object->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/', null, 'clientfolders');
		if (!empty($data['errors'])) return array();
		$client=$data['response'][0];
		if (empty($client)) return array();
		$data=$_object->icontact_makecall($appid, $apiusername, $apipassword, '/a/'.$account->accountId.'/c/'.$client->clientFolderId.'/lists', array(), 'lists');
		if (!empty($data['errors'])) return array();
		if (!is_array($data['response'])) return array();
		$lists=array();
		foreach ($data['response'] as $list) {
			$lists[$list->listId]=$list->name;
		}
		return $lists;
	}

	public function icontact_makecall($appid, $apiusername, $apipassword, $resource, $postdata=null, $returnkey=null) {
		$return=array();
		$url="https://app.icontact.com/icp".$resource;
		$headers=array(
			'Except:', 
			'Accept:  application/json', 
			'Content-type:  application/json', 
			'Api-Version:  2.2',
			'Api-AppId:  '.$appid, 
			'Api-Username:  '.$apiusername, 
			'Api-Password:  '.$apipassword
		);
		$handle=curl_init();
		curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		if (!empty($postdata)) {
			curl_setopt($handle, CURLOPT_POST, true);
			curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($postdata));
		}
		curl_setopt($handle, CURLOPT_URL, $url);
		if (!$response_json=curl_exec($handle)) {
			$return['errors'][]='Unable to execute the cURL handle.';
		}
		if (!$response=json_decode($response_json)) {
			$return['errors'][]='The iContact API did not return valid JSON.';
		}
		curl_close($handle);
		if (!empty($response->errors)) {
			foreach ($response->errors as $error) {
				$return['errors'][]=$error;
			}
		}
		if (!empty($return['errors'])) return $return;
		if (empty($returnkey)) {
			$return['response']=$response;
		} else {
			$return['response']=$response->$returnkey;
		}
		return $return;
	}

	public function getresponse_getcampaigns($api_key) {
		$request=json_encode(
			array(
				'method' => 'get_campaigns',
				'params' => array(
					$api_key
				),
				'id' => ''
			)
		);
		$curl=curl_init('https://api2.getresponse.com/');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		$header=array(
			'Content-Type: application/json',
			'Content-Length: '.strlen($request)
		);
		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // verify certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // check existence of CN and verify that it matches hostname
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		$response=curl_exec($curl);
		if (curl_error($curl)) return array();
		$httpCode=curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($httpCode != '200') return array();
		curl_close($curl);
		$post=json_decode($response, true);
		if(!empty($post['error'])) return array();
		if (empty($post['result'])) return array();
		$campaigns=array();
		foreach ($post['result'] as $key => $value) {
			$campaigns[$key]=$value['name'];
		}
		return $campaigns;
	}

	public function madmimi_getlists($_login, $_api_key) {
		$curl=curl_init('http://api.madmimi.com/audience_lists/lists.json?'.http_build_query(array('username' => $_login, 'api_key' => $_api_key)));
		curl_setopt($curl, CURLOPT_POST, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
										
		$response=curl_exec($curl);
							
		if (curl_error($curl)) return array();
		$httpCode=curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($httpCode != '200') return array();
		curl_close($curl);
							
		$result=json_decode($response, true);
		if(!$result) return array();
		$lists=array();
		foreach ($result as $key => $value) {
			$lists[$value['id']]=$value['name'];
		}
		return $lists;
	}

	public function benchmark_getlists($_key) {
		$request=http_build_query(array(
			'token' => $_key
		));

		$curl=curl_init('http://www.benchmarkemail.com/api/1.0/?output=php&method=listGet');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
									
		$response=curl_exec($curl);
		curl_close($curl);

		$result=unserialize($response);
		if (!is_array($result) || isset($result['error'])) return array();
		$lists=array();
		foreach ($result as $key => $value) {
			$lists[$value['id']]=$value['listname'];
		}
		return $lists;
	}
	public function activecampaign_getlists($_url, $_key) {
		$request=http_build_query(array(
			'api_action' => 'list_list',
			'api_key' => $_key,
			'api_output' => 'serialize',
			'ids' => 'all'
		));

		$_url=str_replace('https://', 'http://', $_url);
		$curl=curl_init($_url.'/admin/api.php?api_action=list_list');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
									
		$response=curl_exec($curl);
		
		curl_close($curl);
		$result=unserialize($response);
		if (!is_array($result) || (isset($result['result_code']) && $result['result_code'] != 1)) return array();
		$lists=array();
		foreach ($result as $key => $value) {
			if (is_array($value)) {
				$lists[$value['id']]=$value['name'];
			}
		}
		return $lists;
	}

	public function interspire_getlists($_url, $_username, $_token) {
		$lists=array();
		try {
			$xml='
<xmlrequest>
	<username>'.$_username.'</username>
	<usertoken>'.$_token.'</usertoken>
	<requesttype>lists</requesttype>
	<requestmethod>GetLists</requestmethod>
	<details>
	</details>
</xmlrequest>';
			$curl=curl_init($_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
			$response=curl_exec($curl);
			curl_close($curl);
			$p=xml_parser_create();
			xml_parse_into_struct($p, $response, $values, $index);
			xml_parser_free($p);
			if (isset($index['STATUS']) && $values[$index['STATUS'][0]]['value'] == 'SUCCESS') {
				$i=0;
				foreach ($index['LISTID'] as $idx) {
					$lists[$values[$idx]['value']]=$values[$index['NAME'][$i]]['value'];
					$i++;
				}
			}
			ksort($lists);
		} catch (Exception $e) {
		}
		return $lists;
	}
	public function interspire_getfields($_url, $_username, $_token, $_listid) {
		$fields=array();
		try {
			$xml='
<xmlrequest>
	<username>'.$_username.'</username>
	<usertoken>'.$_token.'</usertoken>
	<requesttype>lists</requesttype>
	<requestmethod>GetCustomFields</requestmethod>
	<details>
		<listids>'.$_listid.'</listids>
	</details>
</xmlrequest>';
			$curl=curl_init($_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
			$response=curl_exec($curl);
			curl_close($curl);
			$p=xml_parser_create();
			xml_parse_into_struct($p, $response, $values, $index);
			xml_parser_free($p);
			if (isset($index['STATUS']) && $values[$index['STATUS'][0]]['value'] == 'SUCCESS') {
				$i=0;
				foreach ($index['FIELDID'] as $idx) {
					$fields[$values[$idx]['value']]=$values[$index['NAME'][$i]]['value'];
					$i++;
				}
			}
			ksort($fields);
		} catch (Exception $e) {
		}
		return $fields;
	}
}
?>