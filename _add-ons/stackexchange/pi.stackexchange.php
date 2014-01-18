<?php

class Plugin_stackexchange extends Plugin
{

	public $meta = array(
		'name'       => 'Stack Exchange',
		'version'    => 1.0,
		'author'     => 'Jason Varga',
		'author_url' => 'http://pixelfear.com'
	);

	protected $endpoint;
	protected $site;

	function __construct()
	{
		parent::__construct();

		$this->endpoint = 'http://api.stackexchange.com/2.1';
		$this->site = $this->config['site'];
	}

	public function tagged_questions()
	{
		$tag      = $this->fetchParam('tag');
		$sort_by  = $this->fetchParam('sort_by', 'relevance');
		$sort_dir = $this->fetchParam('sort_dir', 'desc');
		$limit    = $this->fetchParam('limit', 100);

		if ($tag) {

			$url = $this->endpoint . '/search';
			$query = http_build_query(array(
				'tagged'   => $tag,
				'sort'     => $sort_by,
				'order'    => $sort_dir,
				'pagesize' => $limit,
				'site'     => $this->site
			));
			$url = $url . '?' . $query;
			
			$questions = json_decode($this->performRequest($url))->items;

			if (count($questions) == 0) {
				return Parse::template($this->content, array(
					'no_results' => true,
					'tag'        => $tag
				));
			}

			foreach ($questions as $i => $question) {
				$data[] = array(
					// question data
					'question_id' => $question->question_id,
					'title'       => $question->title,
					'url'         => $question->link,
					'date'        => $question->creation_date,
					'tags'        => $question->tags,
					'asker'       => array(
						'name'   => $question->owner->display_name,
						'url'    => $question->owner->link,
						'avatar' => $question->owner->profile_image
					),
					// contextual/helper values
					'zero_index'    => $i,
					'index'         => $i + 1,
					'count'         => $i + 1,
					'first'         => ($i === 0) ? true : false,
					'last'          => ($i === count($questions)-1) ? true : false,
					'total_results' => count($questions),
					'tag'           => $tag
				);
			}

			return Parse::tagLoop($this->content, $data);

		}
		else {
			$this->log->error('No tag specified.');
			return false;
		}
	}

	public function search_results()
	{
		$query_param = $this->config['query_variable'];
		$query       = Request::get($query_param);
		$sort_by     = Request::get('sort_by', $this->fetchParam('sort_by', 'relevance'));
		$sort_dir    = Request::get('sort_dir', $this->fetchParam('sort_dir', 'desc'));
		$limit       = Request::get('limit', $this->fetchParam('limit', 100));

		// No query specified
		if (!$query) {
			return Parse::template($this->content, array(
				'no_query'   => true,
				'no_results' => true,
				$query_param => $query
			));
		}

		$url = $this->endpoint . '/search/advanced';
		$query = http_build_query(array(
			'q'        => $query,
			'sort'     => $sort_by,
			'order'    => $sort_dir,
			'pagesize' => $limit,
			'site'     => $this->site
		));
		$url = $url . '?' . $query;
		
		$questions = json_decode($this->performRequest($url))->items;

		if (count($questions) == 0) {
			return Parse::template($this->content, array(
				'no_results' => true,
				$query_param => $query
			));
		}

		foreach ($questions as $i => $question) {
			$data[] = array(
				// question data
				'question_id' => $question->question_id,
				'title'       => $question->title,
				'url'         => $question->link,
				'date'        => $question->creation_date,
				'tags'        => $question->tags,
				'asker'       => array(
					'name'   => $question->owner->display_name,
					'url'    => $question->owner->link,
					'avatar' => $question->owner->profile_image
				),
				// contextual/helper values
				'zero_index'    => $i,
				'index'         => $i + 1,
				'count'         => $i + 1,
				'first'         => ($i === 0) ? true : false,
				'last'          => ($i === count($questions)-1) ? true : false,
				'total_results' => count($questions),
				$query_param    => $query
			);
		}

		return Parse::tagLoop($this->content, $data);
	}

	public function search_query()
	{
		$query_param = $this->config['query_variable'];
		return Request::get($query_param);
	}

	private function performRequest($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}

}