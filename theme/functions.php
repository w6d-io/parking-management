<?php
// Enqueue styles for the single Mantine card element
function mantine_card_styles() {
	wp_add_inline_style('wp-block-library', '
        .mantine-card {
            background: white;
            border-radius: 8px;
            padding: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            height: 340px;
            margin-bottom: 1.5rem;
        }

        .mantine-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
            border-color: #228be6;
        }

        .mantine-card-image {
            width: 100%;
            height: 120px;
            border-radius: 0;
            margin: 0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .mantine-card:hover .mantine-card-image {
            transform: scale(1.02);
        }

        .mantine-card-image.no-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .mantine-card-image.gradient-blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .mantine-card-image.gradient-pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .mantine-card-image.gradient-cyan { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .mantine-card-image.gradient-green { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .mantine-card-image.gradient-orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .mantine-card-image.gradient-purple { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }

        .mantine-card-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            width: fit-content;
        }

        .mantine-card-badge.badge-blue { background: #e3f2fd; color: #1976d2; }
        .mantine-card-badge.badge-green { background: #e8f5e8; color: #388e3c; }
        .mantine-card-badge.badge-orange { background: #fff3e0; color: #f57c00; }
        .mantine-card-badge.badge-red { background: #fce4ec; color: #c2185b; }
        .mantine-card-badge.badge-purple { background: #f3e5f5; color: #7b1fa2; }
        .mantine-card-badge.badge-gray { background: #f5f5f5; color: #616161; }

        .mantine-card-header {
            margin-bottom: 1rem;
        }

        .mantine-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            padding: 0 1.5rem;
        }

        .mantine-card-subtitle {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
            line-height: 1.4;
        }

        .mantine-card-content {
            flex: 1;
            margin-bottom: 1rem;
            padding: 0 1.5rem;
        }

        .mantine-card-text {
            color: black;
            font-size: 1.3rem;
            line-height: 1.5;
        }

        .mantine-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f1f3f4;
            margin-top: auto;
        }

        .mantine-card-button {
            background: #228be6;
            color: white !important;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .mantine-card-button:hover {
            background: #1971c2 !important;
            color: white !important;
            text-decoration: none;
        }

        .mantine-card-button.button-green { background: #40c057; }
        .mantine-card-button.button-green:hover { background: #37b24d !important; }
        .mantine-card-button.button-red { background: #fa5252; }
        .mantine-card-button.button-red:hover { background: #f03e3e !important; }
        .mantine-card-button.button-orange { background: #fd7e14; }
        .mantine-card-button.button-orange:hover { background: #e8590c !important; }
        .mantine-card-button.button-purple { background: #be4bdb; }
        .mantine-card-button.button-purple:hover { background: #ae3ec9 !important; }

        .mantine-card-meta {
            font-size: 0.75rem;
            color: #868e96;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mantine-card-icon-text {
            font-size: 2rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        /* Ensure equal height cards in WPBakery columns */
        .vc_row .vc_column_container {
            display: flex;
        }

        .vc_row .vc_column_container > .vc_column-inner {
            display: flex;
            flex: 1;
        }

        .vc_row .wpb_column > .vc_column-inner > .wpb_wrapper {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        /* Hide empty elements */
        .mantine-card-badge:empty,
        .mantine-card-title:empty,
        .mantine-card-subtitle:empty,
        .mantine-card-text:empty,
        .mantine-card-meta:empty {
            display: none;
        }

        .mantine-card-header:empty {
            margin-bottom: 0;
        }

        .mantine-card-content:empty {
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .mantine-card {
                margin-bottom: 1rem;
            }

            .mantine-card-footer {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
        }
    ');
}
add_action('wp_enqueue_scripts', 'mantine_card_styles');

// Create the shortcode function for single card
function mantine_card_shortcode($atts, $content = null) {
	$atts = shortcode_atts(array(
		'image' => '',
		'gradient' => 'blue',
		'icon' => '',
		'badge' => '',
		'badge_color' => 'blue',
		'title' => 'Card Title',
		'subtitle' => '',
		'button_text' => 'Learn More',
		'button_url' => '#',
		'button_color' => 'blue',
		'meta_text' => '',
		'height' => '120'
	), $atts);

	$image_html = '';
	if (!empty($atts['image'])) {
		// If image ID is provided
		if (is_numeric($atts['image'])) {
			$image_url = wp_get_attachment_image_url($atts['image'], 'medium');
			if ($image_url) {
				$image_html = '<div class="mantine-card-image" style="background-image: url(' . esc_url($image_url) . '); height: ' . intval($atts['height']) . 'px;">';
			} else {
				$image_html = '<div class="mantine-card-image gradient-' . esc_attr($atts['gradient']) . '" style="height: ' . intval($atts['height']) . 'px;">';
			}
		} else {
			// If direct URL is provided
			$image_html = '<div class="mantine-card-image" style="background-image: url(' . esc_url($atts['image']) . '); height: ' . intval($atts['height']) . 'px;">';
		}
	} else {
		$image_html = '<div class="mantine-card-image gradient-' . esc_attr($atts['gradient']) . '" style="height: ' . intval($atts['height']) . 'px;">';
	}

	if (!empty($atts['icon'])) {
		$image_html .= '<span class="mantine-card-icon-text">' . esc_html($atts['icon']) . '</span>';
	}
	$image_html .= '</div>';

	$output = '<div class="mantine-card">';
	$output .= $image_html;

	if (!empty($atts['badge'])) {
		$output .= '<div class="mantine-card-badge badge-' . esc_attr($atts['badge_color']) . '">' . esc_html($atts['badge']) . '</div>';
	}

	if (!empty($atts['title']) || !empty($atts['subtitle'])) {
		$output .= '<div class="mantine-card-header">';
		if (!empty($atts['title'])) {
			$output .= '<div class="mantine-card-title">' . esc_html($atts['title']) . '</div>';
		}
		if (!empty($atts['subtitle'])) {
			$output .= '<div class="mantine-card-subtitle">' . esc_html($atts['subtitle']) . '</div>';
		}
		$output .= '</div>';
	}

	if (!empty($content)) {
		$output .= '<div class="mantine-card-content">';
		$output .= '<div class="mantine-card-text">' . wp_kses_post($content) . '</div>';
		$output .= '</div>';
	}

	if (!empty($atts['button_text']) || !empty($atts['meta_text'])) {
		$output .= '<div class="mantine-card-footer">';

		if (!empty($atts['button_text'])) {
			$output .= '<a href="' . esc_url($atts['button_url']) . '" class="mantine-card-button button-' . esc_attr($atts['button_color']) . '">' . esc_html($atts['button_text']) . '</a>';
		}

		if (!empty($atts['meta_text'])) {
			$output .= '<div class="mantine-card-meta">';
			$output .= '<span>' . esc_html($atts['meta_text']) . '</span>';
			$output .= '</div>';
		}

		$output .= '</div>';
	}

	$output .= '</div>';

	return $output;
}
add_shortcode('mantine_card', 'mantine_card_shortcode');

// Register the custom WPBakery element
add_action('vc_before_init', 'mantine_card_vc_element');
function mantine_card_vc_element() {
	vc_map(array(
		'name' => __('Mantine Card', 'textdomain'),
		'base' => 'mantine_card',
		'description' => __('Single Mantine-style card with customizable content', 'textdomain'),
		'category' => __('Custom Elements', 'textdomain'),
		'icon' => 'vc_icon-vc-gitem-post-title',
		'params' => array(
			// Image Section
			array(
				'type' => 'attach_image',
				'heading' => __('Card Image', 'textdomain'),
				'param_name' => 'image',
				'description' => __('Upload an image for the card header. Leave empty to use gradient background.', 'textdomain'),
				'group' => 'Image'
			),
			array(
				'type' => 'dropdown',
				'heading' => __('Gradient Background', 'textdomain'),
				'param_name' => 'gradient',
				'value' => array(
					__('Blue Purple', 'textdomain') => 'blue',
					__('Pink Red', 'textdomain') => 'pink',
					__('Cyan Blue', 'textdomain') => 'cyan',
					__('Green Teal', 'textdomain') => 'green',
					__('Orange Yellow', 'textdomain') => 'orange',
					__('Purple Pink', 'textdomain') => 'purple',
				),
				'std' => 'blue',
				'description' => __('Choose gradient if no image is uploaded', 'textdomain'),
				'group' => 'Image',
				'dependency' => array(
					'element' => 'image',
					'is_empty' => true
				)
			),
			array(
				'type' => 'textfield',
				'heading' => __('Icon/Emoji', 'textdomain'),
				'param_name' => 'icon',
				'description' => __('Add an emoji or icon to display over the image/gradient (e.g., 📊, 🎨, ⚡)', 'textdomain'),
				'group' => 'Image'
			),
			array(
				'type' => 'textfield',
				'heading' => __('Image Height', 'textdomain'),
				'param_name' => 'height',
				'value' => '120',
				'description' => __('Height in pixels (default: 120)', 'textdomain'),
				'group' => 'Image'
			),

			// Content Section
			array(
				'type' => 'textfield',
				'heading' => __('Badge Text', 'textdomain'),
				'param_name' => 'badge',
				'description' => __('Small label badge (e.g., "New", "Popular", "Premium")', 'textdomain'),
				'group' => 'Content'
			),
			array(
				'type' => 'dropdown',
				'heading' => __('Badge Color', 'textdomain'),
				'param_name' => 'badge_color',
				'value' => array(
					__('Blue', 'textdomain') => 'blue',
					__('Green', 'textdomain') => 'green',
					__('Orange', 'textdomain') => 'orange',
					__('Red', 'textdomain') => 'red',
					__('Purple', 'textdomain') => 'purple',
					__('Gray', 'textdomain') => 'gray',
				),
				'std' => 'blue',
				'group' => 'Content',
				'dependency' => array(
					'element' => 'badge',
					'not_empty' => true
				)
			),
			array(
				'type' => 'textfield',
				'heading' => __('Card Title', 'textdomain'),
				'param_name' => 'title',
				'value' => 'Card Title',
				'description' => __('Main heading of the card', 'textdomain'),
				'group' => 'Content',
				'admin_label' => true
			),
			array(
				'type' => 'textfield',
				'heading' => __('Subtitle', 'textdomain'),
				'param_name' => 'subtitle',
				'description' => __('Subtitle or tagline', 'textdomain'),
				'group' => 'Content'
			),
			array(
				'type' => 'textarea',
				'heading' => __('Card Description', 'textdomain'),
				'param_name' => 'content',
				'description' => __('Main content text of the card', 'textdomain'),
				'group' => 'Content'
			),

			// Button Section
			array(
				'type' => 'textfield',
				'heading' => __('Button Text', 'textdomain'),
				'param_name' => 'button_text',
				'value' => 'Learn More',
				'description' => __('Text for the action button', 'textdomain'),
				'group' => 'Button'
			),
			array(
				'type' => 'vc_link',
				'heading' => __('Button Link', 'textdomain'),
				'param_name' => 'button_url',
				'description' => __('URL for the button link', 'textdomain'),
				'group' => 'Button'
			),
			array(
				'type' => 'dropdown',
				'heading' => __('Button Color', 'textdomain'),
				'param_name' => 'button_color',
				'value' => array(
					__('Blue', 'textdomain') => 'blue',
					__('Green', 'textdomain') => 'green',
					__('Red', 'textdomain') => 'red',
					__('Orange', 'textdomain') => 'orange',
					__('Purple', 'textdomain') => 'purple',
				),
				'std' => 'blue',
				'group' => 'Button'
			),
			array(
				'type' => 'textfield',
				'heading' => __('Meta Text', 'textdomain'),
				'param_name' => 'meta_text',
				'description' => __('Small text in footer (e.g., "Updated 2m ago", "5 min read")', 'textdomain'),
				'group' => 'Button'
			),
		)
	));
}

// Add JavaScript for card interactions
function mantine_card_scripts() {
	wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            $(".mantine-card-button").on("click", function(e) {
                // Animation feedback
                var button = $(this);
                button.css("transform", "scale(0.95)");
                setTimeout(function() {
                    button.css("transform", "scale(1)");
                }, 150);
            });

            // Add hover effects
            $(".mantine-card").hover(
                function() {
                    $(this).css("border-color", "#228be6");
                },
                function() {
                    $(this).css("border-color", "#e9ecef");
                }
            );
        });
    ');
}
add_action('wp_enqueue_scripts', 'mantine_card_scripts');
