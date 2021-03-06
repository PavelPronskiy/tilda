<?php

namespace Tags;

abstract class Controller
{
	public static $dom;

	public static function compressHTML($html) : string
	{
		return $html;
		// $parser = \WyriHaximus\HtmlCompress\Factory::constructSmallest();
		// return $parser->compress($html);
	}

	public static function changeAHrefLinks() : void
	{
		foreach (self::$dom->getElementsByTagName('a') as $tag)
		{
			$project_path = \Config\Controller::$domain->project . \Config\Controller::$route->path;
			if ($tag->getAttribute('href') == $project_path) {
				$tag->setAttribute(
					'href',
					\Config\Controller::$route->url
				);
			}
		}
	}

	public static function changeBaseHref() : void
	{
		foreach (self::$dom->getElementsByTagName('base') as $b)
		{
			$b->setAttribute(
				'href',
				\Config\Controller::$route->url
			);
		}
	}

	public static function removeComments() : void
	{
		$xpath = new \DOMXPath(self::$dom);

		while ($node = $xpath->query('//comment()')->item(0))
		{
			$node->parentNode->removeChild($node);
		}
	}	

	public static function changeMetaTags() : void
	{
		$metatags = self::$dom->getElementsByTagName('meta');
		foreach ($metatags as $meta)
		{
			switch (strtolower($meta->getAttribute('itemprop')))
			{
				case 'image':
					switch (\Config\Controller::$config->images)
					{
						case 'relative':
							$content = $meta->getAttribute('content');
							if (!empty($content)) {
								$meta->setAttribute(
									'content',
									'/?img=' . \Encrypt\Controller::encode($content)
								);
							}
						break;
					}
				
				break;
			}

			switch (strtolower($meta->getAttribute('http-equiv'))) {
				case 'x-dns-prefetch-control': break;
			}
			
			switch (strtolower($meta->getAttribute('name'))) {
				case 'robots': $meta->parentNode->removeChild($meta); break;
				case 'generator': $meta->parentNode->removeChild($meta); break;
			}

			switch (strtolower($meta->getAttribute('property'))) {
				case 'og:url':
					$meta->setAttribute(
						'content',
						\Config\Controller::$route->url);
				break;
				case 'og:image':
					switch (\Config\Controller::$config->images) {
						case 'relative':
							$content = $meta->getAttribute('content');
							if (!empty($content)) {
								$meta->setAttribute(
									'content',
									'/?img=' . \Encrypt\Controller::encode($content)
								);
							}
						break;
					}
				break;
			}
		}
	}

	public static function changeLinkTags() : void
	{
		$xpath = new \DOMXPath(self::$dom);
		$nodes = $xpath->query('//style');
		foreach ($nodes as $node)
		{
			$attr = $node->getAttribute('data-url');
			if (!empty($attr)) {
				$node->setAttribute(
					'data-url',
					'/?css=' . \Encrypt\Controller::encode(self::parseURL($attr))
				);
			}

			$attr = $node->getAttribute('data-href');
			if (!empty($attr)) {
				$node->setAttribute(
					'data-href',
					'/?css=' . \Encrypt\Controller::encode(self::parseURL($attr))
				);
			}

			if (preg_match_all('@url\(\"?//[^/]+[^.]+\.[^.]+?\)@i', $node->nodeValue, $match))
			{
				if (count($match[0]) > 0)
				{
					$nodeValue = $node->nodeValue;

					foreach ($match[0] as $str)
					{
						// $str = str_replace('url("', '', $str);
						$str = str_replace('url(', '', $str);
						$str = str_replace(')', '', $str);
						$str = str_replace('"', '', $str);
						// var_dump($str);
						$nodeValue = str_replace($str, '/?font=' . \Encrypt\Controller::encode('https:' . $str), $nodeValue);
					}

					$node->nodeValue = '';
					$node->appendChild(self::$dom->createTextNode($nodeValue));

					// var_dump($nodeValue);
				}
			}
		}

		foreach (self::$dom->getElementsByTagName('link') as $link)
		{
			switch (strtolower($link->getAttribute('rel'))) {
				case 'preload':
					$src = $link->getAttribute('href');
					if (!empty($src)) {
						$link->setAttribute(
							'href',
							'/?js=' . \Encrypt\Controller::encode(self::parseURL($src))
						);
					}
						
					break;
		
				case 'canonical':
					$link->setAttribute(
						'href',
						\Config\Controller::$domain->site
					);
					break;

				case 'icon':
				case 'shortcut icon':
				case 'apple-touch-icon':
					$src = $link->getAttribute('href');
					if (!empty($src)) {
						$link->setAttribute(
							'href',
							'/?ico=' . \Encrypt\Controller::encode(self::parseURL($src))
						);
					}
					break;

				case 'dns-prefetch':
					$link->setAttribute(
						'href',
						\Config\Controller::$domain->site
					);
				break;

				case 'stylesheet':
					switch (\Config\Controller::$config->styles) {
						case 'relative':
							$src = $link->getAttribute('href');
							if (!empty($src)) {
								$link->setAttribute(
									'href',
									'/?css=' . \Encrypt\Controller::encode(self::parseURL($src))
								);
							}
						break;
					}
				break;

			}
		}

	}

	public static function changeScriptTags() : void
	{
		foreach (self::$dom->getElementsByTagName('script') as $index => $script)
		{
			switch (\Config\Controller::$config->scripts)
			{
				case 'relative':
					$src = $script->getAttribute('src');

					switch ($script->getAttribute('id'))
					{
						case 'sentry':
							$script->parentNode->removeChild($script);
							break;

						case 'wix-viewer-model':
							break;
						
					}

					if (!empty($src))
					{
						$script->setAttribute(
							'src',
							'/?js=' . \Encrypt\Controller::encode(self::parseURL($src))
						);
					}

					$data_url = $script->getAttribute('data-url');
					if (!empty($data_url))
					{
						$script->setAttribute(
							'data-url',
							'/?js=' . \Encrypt\Controller::encode(self::parseURL($data_url))
						);
					}

				break;
			}
		}
	}

	public static function changeImgTags() : void
	{
		$imgs = self::$dom->getElementsByTagName('img');
		foreach ($imgs as $img)
		{
			switch (\Config\Controller::$config->images) {
				case 'relative':
					$src = $img->getAttribute('src');
					if (!empty($src)) {
						$img->setAttribute(
							'src',
							'/?img=' . \Encrypt\Controller::encode(self::parseURL($src))
						);
					}
				break;
			}
		}

		$images = self::$dom->getElementsByTagName('image');
		foreach ($images as $img)
		{
			switch (\Config\Controller::$config->images) {
				case 'relative':
					$src = $img->getAttribute('xlink:href');
					if (!empty($src)) {
						$img->setAttribute(
							'xlink:href',
							'/?img=' . \Encrypt\Controller::encode(self::parseURL($src))
						);
					}
				break;
			}
		}
	}

	public static function parseURL($src) : string
	{
		$url = parse_url($src);

		return isset($url['host'])
			? $src
			: \Config\Controller::$domain->project . $src;
	}

	public static function stripHTML($html) : string
	{

		$dom_html5 = new \Masterminds\HTML5(['disable_html_ns' => true]);
		$html = self::compressHTML($html);
		self::$dom = $dom_html5->loadHTML($html);
		self::changeBaseHref();
		self::changeImgTags();
		self::changeScriptTags();
		self::changeLinkTags();
		self::changeMetaTags();
		self::removeComments();
		self::changeAHrefLinks();

		switch (\Config\Controller::$domain->type) {
			case 'wix':
				Wix::changeWixOptions();
				Wix::changeHtmlTags();
			break;

			case 'tilda':
				Tilda::removeTildaCopy();
			break;
		}


		return self::$dom->saveHTML();
	}
}

class Wix extends Controller
{
	public static function changeWixOptions() : void
	{
		$xpath = new \DOMXPath(self::$dom);
		$nodes = $xpath->query('//script[@id="wix-viewer-model"]');

		foreach ($nodes as $key => $node) {
			$dec = json_decode($node->nodeValue);

			if (isset($dec->siteFeaturesConfigs->platform->bootstrapData->location->domain))
				$dec->siteFeaturesConfigs->platform->bootstrapData->location->domain = \Config\Controller::$route->domain;

			if (isset($dec->siteFeaturesConfigs->platform->bootstrapData->location->externalBaseUrl))
				$dec->siteFeaturesConfigs->platform->bootstrapData->location->externalBaseUrl = \Config\Controller::$route->url;
			
			if (isset($dec->site->externalBaseUrl))
				$dec->site->externalBaseUrl = \Config\Controller::$route->url;

			if (isset($dec->siteFeaturesConfigs->tpaCommons->externalBaseUrl))
				$dec->siteFeaturesConfigs->tpaCommons->externalBaseUrl = \Config\Controller::$route->url;
			if (isset($dec->siteFeaturesConfigs->router->baseUrl))
				$dec->siteFeaturesConfigs->router->baseUrl = \Config\Controller::$route->url;

			if (isset($dec->siteFeaturesConfigs->seo->context->siteUrl))
				$dec->siteFeaturesConfigs->seo->context->siteUrl = \Config\Controller::$route->url;

			if (isset($dec->siteFeaturesConfigs->seo->context->defaultUrl))
				$dec->siteFeaturesConfigs->seo->context->defaultUrl = \Config\Controller::$route->url;
			
			if (isset($dec->requestUrl))
				$dec->requestUrl = \Config\Controller::$route->url;
			
			if (isset($dec->siteFeaturesConfigs->locationWixCodeSdk->baseUrl))
				$dec->siteFeaturesConfigs->locationWixCodeSdk->baseUrl = \Config\Controller::$route->url;

			if (isset($dec->siteFeaturesConfigs->siteWixCodeSdk->baseUrl))
				$dec->siteFeaturesConfigs->siteWixCodeSdk->baseUrl = \Config\Controller::$route->url;
			
			if (isset($dec->siteFeaturesConfigs->tpaCommons->requestUrl))
				$dec->siteFeaturesConfigs->tpaCommons->requestUrl = \Config\Controller::$route->url;
			
			if (isset($dec->siteAssets->modulesParams->features->externalBaseUrl))
				$dec->siteAssets->modulesParams->features->externalBaseUrl = \Config\Controller::$route->url;

			if (isset($dec->siteAssets->modulesParams->platform->externalBaseUrl))
				$dec->siteAssets->modulesParams->platform->externalBaseUrl = \Config\Controller::$route->url;

			$node->nodeValue = '';
			$node->appendChild(self::$dom->createTextNode(json_encode($dec)));
			// $dec->siteFeaturesConfigs = '';
		}

		$nodes = $xpath->query('//script[@id="wix-fedops"]');

		foreach ($nodes as $key => $node) {
			$dec = json_decode($node->nodeValue);
			$dec->data->site->externalBaseUrl = \Config\Controller::$route->url;
			$dec->data->requestUrl = \Config\Controller::$route->url;

			$node->nodeValue = '';
			$node->appendChild(self::$dom->createTextNode(json_encode($dec)));
		}
	}

	public static function changeHtmlTags() : void
	{
		foreach (self::$dom->getElementsByTagName('div') as $tag)
		{
			// site-root
			if ($tag->getAttribute('id') == 'WIX_ADS') {
				$tag->setAttribute('style', 'display:none');
			}

			if ($tag->getAttribute('id') == 'site-root') {
				$tag->setAttribute('style', 'top:0px');
			}
		}
	}
} 

class Tilda extends Controller
{
	public static function removeTildaCopy() : void
	{
		$tildacopy = self::$dom->getElementById('tildacopy');

		if ($tildacopy) {
			$tildacopy->parentNode->removeChild($tildacopy);
		}
	}
}