<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Pagination class
*/
class Pagination {
	public $total = 0;
	public $page = 1;
	public $limit = 20;
	public $num_links = 8;
	public $url = '';
	public $text_first = '|&lt;';
	public $text_last = '&gt;|';
	public $text_next = '&gt;';
	public $text_prev = '&lt;';

	/**
     * 
     *
     * @return	text
     */
	public function render() {
		$total = $this->total;

		if ($this->page < 1) {
			$page = 1;
		} else {
			$page = $this->page;
		}

		if (!(int)$this->limit) {
			$limit = 10;
		} else {
			$limit = $this->limit;
		}

		$num_links = $this->num_links;
		$num_pages = ceil($total / $limit);

		$this->url = str_replace('%7Bpage%7D', '{page}', $this->url);

		$output = '<nav aria-label="Search results page"><ul class="pagination">';

		if ($page > 1) {
			$output .= '<li class="page-item"><a class="page-link" href="' . str_replace(array('&amp;page={page}', '?page={page}', '&page={page}'), '', $this->url) . '">First</a></li>';
			
			/*if ($page - 1 === 1) {
				$output .= '<li class="page-item"><a class="page-link" href="' . str_replace(array('&amp;page={page}', '?page={page}', '&page={page}'), '', $this->url) . '">Previus</a></li>';
			} else {
				$output .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $page - 1, $this->url) . '">' . $this->text_prev . '</a></li>';
			}*/
		}

		if ($num_pages > 1) {
			if ($num_pages <= $num_links) {
				$start = 1;
				$end = $num_pages;
			} else {
				$start = $page - floor($num_links / 2);
				$end = $page + floor($num_links / 2);

				if ($start < 1) {
					$end += abs($start) + 1;
					$start = 1;
				}

				if ($end > $num_pages) {
					$start -= ($end - $num_pages);
					$end = $num_pages;
				}
			}

			for ($i = $start; $i <= $end; $i++) {
				if ($page == $i) {
					$output .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
				} else {
					if ($i === 1) {
						$output .= '<li class="page-item"><a class="page-link" href="' . str_replace(array('&amp;page={page}', '?page={page}', '&page={page}'), '', $this->url) . '">' . $i . '</a></li>';
					} else {
						$output .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $i, $this->url) . '">' . $i . '</a></li>';
					}
				}
			}
		}

		if ($page < $num_pages) {
			//$output .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $page + 1, $this->url) . '">Next</a></li>';
			$output .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $num_pages, $this->url) . '">Last</a></li>';
		}

		$output .= '</ul></nav>';

		if ($num_pages > 1) {
			return $output;
		} else {
			return '';
		}
	}
}
