<?php
class ControllerProductProduct extends Controller {
	private $error = array();

	public function index() {


		$this->load->language('product/product');

		$data['breadcrumbs'] = array();

        $home_text = $this->language->get('text_home') . ' Safety Signs';
		$data['breadcrumbs'][] = array(
			'text' => 'Safety Signs',
			'href' => $this->url->link('common/home')
		);

		$this->load->model('catalog/category');

		if (isset($this->request->get['path'])) {
			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = $path_id;
				} else {
					$path .= '_' . $path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path)
					);
				}
			}

			// Set the last category breadcrumb
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$url = '';

				if (isset($this->request->get['sort'])) {
					$url .= '&sort=' . $this->request->get['sort'];
				}

				if (isset($this->request->get['order'])) {
					$url .= '&order=' . $this->request->get['order'];
				}

				if (isset($this->request->get['page'])) {
					$url .= '&page=' . $this->request->get['page'];
				}

				if (isset($this->request->get['limit'])) {
					$url .= '&limit=' . $this->request->get['limit'];
				}

				$data['breadcrumbs'][] = array(
					'text' => $category_info['name'],
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url)
				);

			}
		}

		$this->load->model('catalog/manufacturer');

		if (isset($this->request->get['manufacturer_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_brand'),
				'href' => $this->url->link('product/manufacturer')
			);

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);

			if ($manufacturer_info) {
				$data['breadcrumbs'][] = array(
					'text' => $manufacturer_info['name'],
					'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
				);
			}
		}

		if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . $this->request->get['tag'];
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_search'),
				'href' => $this->url->link('product/search', $url)
			);
		}

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . $this->request->get['tag'];
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $product_info['name'],
				'href' => $this->url->link('product/product', $url . '&product_id=' . $this->request->get['product_id'])
			);


			$this->document->setTitle($product_info['meta_title']);
			$this->document->setDescription($product_info['meta_description']);
			$this->document->setKeywords($product_info['meta_keyword']);
			$this->document->addLink($this->url->link('product/product', 'product_id=' . $this->request->get['product_id']), 'canonical');
			$this->document->addScript('/catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
			$this->document->addScript('/catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
			$this->document->addScript('/catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
			$this->document->addStyle('/catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');


			$data['heading_title'] = $product_info['title'];

			$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));

			$this->load->model('catalog/review');

			$data['tab_review'] = sprintf($this->language->get('tab_review'), '' /*$product_info['reviews']*/);

			$data['product_id'] = (int)$this->request->get['product_id'];
			$data['manufacturer'] = $product_info['manufacturer'];
			$data['manufacturers'] = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id']);
			$data['model'] = $product_info['model'];
			$data['reward'] = ''; /*$product_info['reward'];*/
			$data['points'] = 0; /*$product_info['points'];*/
            if($product_info['description']) {
                $data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');
            }
            else {
                $data['description'] = '';
            }
			if($product_info['long_description']){
                $data['long_description']  = html_entity_decode($product_info['long_description'], ENT_QUOTES, 'UTF-8');
            }
            else {
                $data['long_description']  = '';
            }

            $data['mib_logo']  = $product_info['mib_logo'];
            $data['image_path'] = USE_CDN ? TSG_CDN_URL : 'image/';

			/*if ($product_info['quantity'] <= 0) {
				$data['stock'] = $product_info['stock_status'];
			} elseif ($this->config->get('config_stock_display')) {
				$data['stock'] = $product_info['quantity'];
			} else {
				$data['stock'] = $this->language->get('text_instock');
			}
            */

			$this->load->model('tool/image');

			if ($product_info['image']) {
				$data['popup'] = $this->model_tool_image->getImage($product_info['image']);
                if( pathinfo($product_info['image'], PATHINFO_EXTENSION) == 'svg')
                {
                    $data['thumb_css'] = 'img-svg-border';
                }
			} else {
				$data['popup'] = '';
			}

			if ($product_info['image']) {
				$data['thumb'] = $this->model_tool_image->getImage($product_info['image']);
			} else {
				$data['thumb'] = '';
			}

			$data['images'] = array();

			$results = $this->model_catalog_product->getProductImages($this->request->get['product_id']);
			foreach ($results as $result) {
                $additional_css = '';
                if( pathinfo($result['image'], PATHINFO_EXTENSION) == 'svg')
                    $additional_css = 'img-svg-border';
				$data['images'][] = array(
					'popup' => $this->model_tool_image->getImage($result['image']),
					'thumb' => $this->model_tool_image->getImage($result['image']),
                    'additional_images_css' => $additional_css
                );
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}

			/*if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
				$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$tax_price = (float)$product_info['special'];
			} else {
				$data['special'] = false;
				$tax_price = (float)$product_info['price'];
			}
            */
            $tax_price = (float)$product_info['price'];
			if ($this->config->get('config_tax')) {
				$data['tax'] = $this->currency->format($tax_price, $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			//$discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);

			

			/*foreach ($discounts as $discount) {
				$data['discounts'][] = array(
					'quantity' => $discount['quantity'],
					'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
				);
			}*/

			$data['discounts'] = array();

			$data['options'] = array();

			foreach ($this->model_catalog_product->getProductOptions($this->request->get['product_id']) as $option) {
				$product_option_value_data = array();

				foreach ($option['product_option_value'] as $option_value) {
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
						} else {
							$price = false;
						}

						$product_option_value_data[] = array(
							'product_option_value_id' => $option_value['product_option_value_id'],
							'option_value_id'         => $option_value['option_value_id'],
							'name'                    => $option_value['name'],
							'image'                   => $this->model_tool_image->getImage($option_value['image']),
							'price'                   => $price,
							'price_prefix'            => $option_value['price_prefix']
						);
					}
				}

				$data['options'][] = array(
					'product_option_id'    => $option['product_option_id'],
					'product_option_value' => $product_option_value_data,
					'option_id'            => $option['option_id'],
					'name'                 => $option['name'],
					'type'                 => $option['type'],
					'value'                => $option['value'],
					'required'             => $option['required']
				);
			}

			if ($product_info['minimum']) {
				$data['minimum'] = $product_info['minimum'];
			} else {
				$data['minimum'] = 1;
			}

			/*$data['review_status'] = $this->config->get('config_review_status');

			if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
				$data['review_guest'] = true;
			} else {
				$data['review_guest'] = false;
			}*/

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
			}

			$data['reviews'] = '';//sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);
			$data['rating'] = 0 ;//(int)$product_info['rating'];

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$data['captcha'] = '';
			}

			$data['share'] = $this->url->link('product/product', 'product_id=' . (int)$this->request->get['product_id']);

			$data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);

			$data['products'] = array();

			$results = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);

			foreach ($results as $result) {
				if ($result['image']) {
					$image =  $this->model_tool_image->getImage($result['image']);
				} else {
					$image = $this->model_tool_image->getImage('stores/no-image.png');
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				/*if (!is_null($result['special']) && (float)$result['special'] >= 0) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$tax_price = (float)$result['special'];
				} else {
					$special = false;
					$tax_price = (float)$result['price'];
				}*/
                $special = false;
                $tax_price = (float)$result['price'];
	
				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format($tax_price, $this->session->data['currency']);
				} else {
					$tax = false;
				}

				/*if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}*/
                $rating = false;

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
                    'title'        => $result['title'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => 1, //$result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id']),
				);
			}

			$data['tags'] = array();

			if ($product_info['tag']) {
				$tags = explode(',', $product_info['tag']);

				foreach ($tags as $tag) {
					$data['tags'][] = array(
						'tag'  => trim($tag),
						'href' => $this->url->link('product/search', 'tag=' . trim($tag))
					);
				}
			}
			$data['related'] = $this->load->view('tsg/related_products', $data);
			if(sizeof($results) > 0) {
                $data['has_related'] = 1;
            }
			else {
                $data['has_related'] = 0;
            }


			$data['recurrings'] = $this->model_catalog_product->getProfiles($this->request->get['product_id']);

			$this->model_catalog_product->updateViewed($this->request->get['product_id']);


            //TSG - load in the variants for this product

            $variant_data = $this->load->controller('tsg/product_variants');


            $data['variants'] = $variant_data['options_section'];
            $data['variants_table'] = $variant_data['variants_table'];
            $data['option_bulk_table'] = $variant_data['option_bulk_table'];

            $data['extra_info'] = $this->load->controller('tsg/extra_info');

            $data['product_markup'] =$this->load->controller('tsg/product_variant_markup');




            $this->load->model('tsg/product_variants');
            $data['MaterialsDesc'] = array();
            $data['MaterialsDesc'] =  $this->model_tsg_product_variants->getMaterialDescriptions($this->request->get['product_id']);

            $template_name = $this->model_catalog_product->getProductTemplate($this->request->get['product_id']);
            $data['is_bespoke'] = 0;
            $data_bespoke['selected_symbol_id'] = 0;


            $product_symbol =  $this->model_catalog_product->getProductSymbols($this->request->get['product_id']);
            if(sizeof($product_symbol) === 1) {
                $data['has_bespoke'] = 1;
                $bespokelink = $this->url->link('product/product', 'product_id=' . $this->request->get['product_id'] . '&makebespoke=1');
                $data['bespoke_link'] = $bespokelink;
            }


            //testing
            //$data['has_bespoke'] = 1;





            if(isset($this->request->get['makebespoke'])) {
                //get  symbols for this product
                if (sizeof($product_symbol) == 1) {
                    $data['is_bespoke'] = 1;
                    //$template_name = $template_name = $this->model_catalog_product->getProductBespokeTemplate($this->request->get['product_id']);
                    $product_template_row = $this->model_catalog_product->getProductBespokeTemplatePath($this->request->get['product_id']);
                    if($product_template_row) {
                        $template_name = $product_template_row[0]['path'];
                    }
                    else
                    {
                        $template_name = 'bespoke/single_panel';
                    }

                }
            }

            if($product_info['is_bespoke'] || $data['is_bespoke'])
            {
                //load the model
                $this->load->model('bespoke/bespoke');

                $this->document->addScript('/catalog/view/javascript/bespoke/interface.js');
                $this->document->addScript('/catalog/view/javascript/bespoke/bespoke.js');
                $this->document->addScript('/catalog/view/javascript/bespoke/bespoke.construct.js');
                $this->document->addScript('/catalog/view/javascript/bespoke/bespoke.draw.js');
                $this->document->addScript('/catalog/view/javascript/bespoke/svg.js');
                $this->document->addScript('/catalog/view/javascript/bespoke/svg.filter.js');
                $this->document->addScript('/catalog/view/javascript/bespoke/svg.screenbbox.js');
                $this->document->addScript('/catalog/view/javascript/tsg/bootstrap-detect-breakpoint.js');
                $this->document->addStyle('/catalog/view/javascript/bespoke/bespoke-text-font/css/bespoke-text.css' );

                $data_bespoke = array();
                $data_bespoke['image_path'] = USE_CDN ? TSG_CDN_URL : 'image/';
                $data_bespoke['initial_text'] = 'Change Me';


                //see of we are loading a bespoke product from the cart
                if (isset($this->request->get['bespokeid'])) {
                    $cartid  = $this->request->get['bespokeid'];
                    $data['bespokeid'] = $cartid;
                    $bespoke_data = $this->model_bespoke_bespoke->getBespokeInfoFromCartId($cartid);
                    $data['has_svg_data'] = 1;
                    $data['svg_data'] = [];
                    $data['svg_data']['svg_raw'] = $bespoke_data['svg_raw'];
                    $data['svg_data']['svg_json'] = json_decode($bespoke_data['svg_json']);
                    $data['svg_data']['svg_export'] = $bespoke_data['svg_export'];
                    $data['svg_data']['svg_images'] = json_decode($bespoke_data['svg_images']);
                    //$data['svg_data']['svg_export'] = $bespoke_data['svg_export'];
                    //$data['svg_data']['svg_images'] = $bespoke_data['svg_images'];
                    $data['svg_data']['svg_texts'] = ($bespoke_data['svg_texts']);
                }
                else {
                    $data['has_svg_data'] = 0;
                    $data['svg_data'] = null;
                }

                if($product_symbol){
                    $data_bespoke['selected_symbol_id'] = $product_symbol[0]['symbol_id'];
                    if($product_info['is_bespoke'])
                    {
                        //get the category for this products symbol
                        $data_bespoke['symbol_category'] =  $this->model_bespoke_bespoke->getSymbolCategoryTypeByProduct($this->request->get['product_id']);
                        $symbol_category_id = $data_bespoke['symbol_category']['category_type_id'];
                    }
                    else
                    {
                        //get the category for this products symbol
                        $data_bespoke['symbol_category'] =  $this->model_bespoke_bespoke->getSymbolCategoryTypeBySymbolId($data_bespoke['selected_symbol_id']);
                        $symbol_category_id = $data_bespoke['symbol_category']['category_type_id'];
                    }
                    //get the symbols for this category
                    $data_bespoke['symbols'] = $this->model_bespoke_bespoke->getSymbolsByCategoryType($symbol_category_id);
                    $data['selected_symbol_info'] = $this->model_bespoke_bespoke->getSymbolInfoBySymbolId($data_bespoke['selected_symbol_id']);


                    //load the template
                    $symbol_layout = $this->load->view('bespoke/symbol_single', $data_bespoke);
                    $data['symbol_layout'] = $symbol_layout;
                }

                //now do the textareas
                $data['bespoke_text_area'] = $this->load->Controller('bespoke/text_area');

                //create the colour pancels
                $data_bespoke['colours'] = TSG_BESPOKE_SINGLE_COLOUR;
                $data_bespoke['init_colour'] = 0;
                $data_bespoke['borders'] = TSG_BESPOKE_SINGLE_TEXTONLY_BORDERS;
                $data_bespoke['init_border'] = 0;

                $data['colours'] = TSG_BESPOKE_SINGLE_COLOUR;
                $data['borders'] = TSG_BESPOKE_SINGLE_TEXTONLY_BORDERS;

                $data['colour_layout'] =  $this->load->view('bespoke/colour_single', $data_bespoke);


                $data['initial_size'] = 2;
                $data['initial_text'] = $data_bespoke['initial_text'];

            }

            $data['product_main_content'] = $this->load->view($template_name, $data);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

            $data['breadcrumb_markup'] = $this->load->view('tsg/breadcrumb_markup', $data);



			$this->response->setOutput($this->load->view('product/product_layout', $data));
		} else {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . $this->request->get['tag'];
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/product', $url . '&product_id=' . $product_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function review() {
		$this->load->language('product/product');

		$this->load->model('catalog/review');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['reviews'] = array();

		$review_total = $this->model_catalog_review->getTotalReviewsByProductId($this->request->get['product_id']);

		$results = $this->model_catalog_review->getReviewsByProductId($this->request->get['product_id'], ($page - 1) * 5, 5);

		foreach ($results as $result) {
			$data['reviews'][] = array(
				'author'     => $result['author'],
				'text'       => nl2br($result['text']),
				'rating'     => (int)$result['rating'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = 5;
		$pagination->url = $this->url->link('product/product/review', 'product_id=' . $this->request->get['product_id'] . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

		$this->response->setOutput($this->load->view('product/review', $data));
	}

	public function write() {
		$this->load->language('product/product');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 25)) {
				$json['error'] = $this->language->get('error_name');
			}

			if ((utf8_strlen($this->request->post['text']) < 25) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error'] = $this->language->get('error_text');
			}

			if (empty($this->request->post['rating']) || $this->request->post['rating'] < 0 || $this->request->post['rating'] > 5) {
				$json['error'] = $this->language->get('error_rating');
			}

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

				if ($captcha) {
					$json['error'] = $captcha;
				}
			}

			if (!isset($json['error'])) {
				$this->load->model('catalog/review');

				$this->model_catalog_review->addReview($this->request->get['product_id'], $this->request->post);

				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getRecurringDescription() {
		$this->load->language('product/product');
		$this->load->model('catalog/product');

		if (isset($this->request->post['product_id'])) {
			$product_id = $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->post['recurring_id'])) {
			$recurring_id = $this->request->post['recurring_id'];
		} else {
			$recurring_id = 0;
		}

		if (isset($this->request->post['quantity'])) {
			$quantity = $this->request->post['quantity'];
		} else {
			$quantity = 1;
		}

		$product_info = $this->model_catalog_product->getProduct($product_id);
		
		$recurring_info = $this->model_catalog_product->getProfile($product_id, $recurring_id);

		$json = array();

		if ($product_info && $recurring_info) {
			if (!$json) {
				$frequencies = array(
					'day'        => $this->language->get('text_day'),
					'week'       => $this->language->get('text_week'),
					'semi_month' => $this->language->get('text_semi_month'),
					'month'      => $this->language->get('text_month'),
					'year'       => $this->language->get('text_year'),
				);

				if ($recurring_info['trial_status'] == 1) {
					$price = $this->currency->format($this->tax->calculate($recurring_info['trial_price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$trial_text = sprintf($this->language->get('text_trial_description'), $price, $recurring_info['trial_cycle'], $frequencies[$recurring_info['trial_frequency']], $recurring_info['trial_duration']) . ' ';
				} else {
					$trial_text = '';
				}

				$price = $this->currency->format($this->tax->calculate($recurring_info['price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

				if ($recurring_info['duration']) {
					$text = $trial_text . sprintf($this->language->get('text_payment_description'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				} else {
					$text = $trial_text . sprintf($this->language->get('text_payment_cancel'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				}

				$json['success'] = $text;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
