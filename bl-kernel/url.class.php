<?php defined('BLUDIT') or die('Bludit CMS.');

class Url
{
	private $uri;
	private $uriStrlen;
	private $whereAmI;
	private $slug;
	private $filters; // Filters for the URI
	private $notFound;
	private $parameters;
	private $activeFilter;
	private $httpCode;
	private $httpMessage;

	function __construct()
	{
		// Decodes any %## encoding in the given string. Plus symbols ('+') are decoded to a space character.
		$decode = urldecode($_SERVER['REQUEST_URI']);

		// Remove parameters GET, I don't use parse_url because has problem with utf-8
		$explode = explode('?', $decode);
		$this->uri = $explode[0];
		$this->parameters = $_GET;
		$this->uriStrlen = Text::length($this->uri);
		$this->whereAmI = 'home';
		$this->notFound = false;
		$this->slug = '';
		$this->filters = array();
		$this->activeFilter = '';
		$this->httpCode = 200;
		$this->httpMessage = 'OK';
	}

	// Filters change for different languages
	// Ex (Spanish): Array('post'=>'/publicacion/', 'tag'=>'/etiqueta/', ....)
	// Ex (English): Array('post'=>'/post/', 'tag'=>'/tag/', ....)
	public function checkFilters($filters)
	{
		// Store the admin filter and remove
		$adminFilter['admin'] = $filters['admin'];
		unset($filters['admin']);

		// Sort filters by length
		uasort($filters, array($this, 'sortByLength'));

		// Push the admin filter first
		$filters = $adminFilter + $filters;
		$this->filters = $filters;

		foreach($filters as $filterName=>$filterURI) {

			// $slug will be FALSE if the filter is not included in the URI
			$slug = $this->getSlugAfterFilter($filterURI);

			if($slug!==false) {
				$this->slug 	= $slug;
				$this->whereAmI = $filterName;
				$this->activeFilter = $filterURI;

				// If the slug is empty
				if( Text::isEmpty($slug) ) {

					if($filterURI==='/') {
						$this->whereAmI = 'home';
						break;
					}

					if($filterURI===$adminFilter['admin']) {
						$this->whereAmI = 'admin';
						$this->slug = 'dashboard';
						break;
					}

					$this->setNotFound();
				}

				break;
			}
		}
	}

	public function slug()
	{
		return $this->slug;
	}

	public function setSlug($slug)
	{
		$this->slug = $slug;
	}

	public function activeFilter()
	{
		return $this->activeFilter;
	}

	public function explodeSlug($delimiter="/")
	{
		return explode($delimiter, $this->slug);
	}

	public function uri()
	{
		return $this->uri;
	}

	// Return the filter used
	public function filters($type, $trim=true)
	{
		$filter = $this->filters[$type];

		if($trim) {
			$filter = trim($filter, '/');
		}

		return $filter;
	}

	// Returns where is the user, home, pages, categories, tags..
	public function whereAmI()
	{
		return $this->whereAmI;
	}

	public function setWhereAmI($where)
	{
		$GLOBALS['WHERE_AM_I'] = $where;
		$this->whereAmI = $where;
	}

	public function notFound()
	{
		return $this->notFound;
	}

	public function pageNumber()
	{
		if(isset($this->parameters['page'])) {
			return (int)$this->parameters['page'];
		}
		return 1;
	}

	public function setNotFound()
	{
		$this->whereAmI = 'page';
		$this->notFound = true;
		$this->httpCode = 404;
		$this->httpMessage = 'Not Found';
	}

	public function httpCode()
	{
		return $this->httpCode;
	}

	public function setHttpCode($code = 200)
	{
		$this->httpCode = $code;
	}

	public function httpMessage()
	{
		return $this->httpMessage;
	}

	public function setHttpMessage($msg = 'OK')
	{
		$this->httpMessage = $msg;
	}

	// Returns the slug after the $filter, the slug could be an empty string
	// If the filter is not included in the uri, returns FALSE
	// ex: http://domain.com/cms/$filter/slug123 => slug123
	// ex: http://domain.com/cms/$filter/name/lastname => name/lastname
	// ex: http://domain.com/cms/$filter/ => empty string
	// ex: http://domain.com/cms/$filter => empty string
	private function getSlugAfterFilter($filter)
	{
		// Remove both slash from the filter
		$filter = trim($filter, '/');

		// Add to the filter the root directory
		$filter = HTML_PATH_ROOT.$filter;

		// Check if the filter is in the uri.
		$position = Text::stringPosition($this->uri, $filter);

		// If the position is FALSE, the filter isn't in the URI.
		if($position===false) {
			return false;
		}

		// Start position to cut
		$start = $position + Text::length($filter);

		// End position to cut
		$end = $this->uriStrlen;

		// Get the slug from the URI
		$slug = Text::cut($this->uri, $start, $end);

		if(Text::isEmpty($slug)) {
			return '';
		}

		if($slug[0]=='/') {
			return ltrim($slug, '/');
		}

		if($filter==HTML_PATH_ROOT) {
			return $slug;
		}

		return false;
	}

	private function sortByLength($a, $b)
	{
		return strlen($b)-strlen($a);
	}

}