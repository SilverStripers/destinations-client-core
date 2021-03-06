<?php

namespace DD\Client\Core;

use DD\Client\Core\Parsers\CategoryParser;
use DD\Client\Core\Parsers\EventParser;
use DD\Client\Core\Parsers\EventsParser;
use DD\Client\Core\Parsers\ListingParser;
use DD\Client\Core\Parsers\ListingsParser;
use DD\Client\Core\Parsers\ListingTagsParser;
use DD\Client\Core\Parsers\LocationParser;
use DD\Client\Core\Parsers\PaginatedListingsParser;
use DD\Client\Core\Parsers\Parser;
use DD\Client\Core\Parsers\RegionParser;
use DD\Client\Core\Parsers\TagParser;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Class Client
 * @package DD\Client\Core
 *
 * @property GuzzleClient $client
 */
class Client
{
    private static $key = null;
    private static $end_point = null;
    private static $referrer = null;
    private $client;

    private static $large_image_width = 2000;
    private static $large_image_height = 950;
    private static $large_image_scale_method = 'FocusFill';
    private static $medium_image_width = 900;
    private static $medium_image_height = 450;
    private static $medium_image_scale_method = 'FocusFill';
    private static $small_image_width = 400;
    private static $small_image_height = 350;
    private static $small_image_scale_method = 'FocusFill';
    private static $logo_image_width = 200;
    private static $logo_image_height = 200;
    private static $logo_image_scale_method = 'FocusFill';
    private static $gallery_image_width = 400;
    private static $gallery_image_height = 400;
    private static $gallery_image_scale_method = 'FocusFill';

    const LIVE = 'Live';
    const DRAFT = 'Stage';

    const ALL = 'All';
    const LISTINGS = 'Listings';
    const EVENTS = 'Events';


    public function __construct($key = null, $endPoint = null, $referrer = null)
    {
        if ($key) {
            self::set_key($key);
        }
        if ($endPoint) {
            self::set_end_point($endPoint);
        }
        if ($referrer) {
            self::set_referrer($referrer);
        }

        if (!self::get_end_point()) {
            throw new \InvalidArgumentException('Destinations Server endpoint not specified');
        }

        $client = new GuzzleClient();
        $this->client = $client;
    }

    public function getCategories($ids = null, $parentIds = null, $includeChildren = false, $all = false)
    {
        $query = QueryLoader::get_query_for(QueryLoader::CATEGORIES);
        $parser = Parser::get_parser_for(CategoryParser::class);
        $vars = [
            'children' => $includeChildren,
            'all' => $all
        ];
        if ($ids) {
            $vars['ids'] = $ids;
        }
        if ($parentIds) {
            $vars['parentIds'] = $parentIds;
        }

        return $parser->parse($this->call(
            $query,
            $vars,
            'categories'
        ));
    }

    public function getCategory($id, $includeChildren = false)
    {
        $query = QueryLoader::get_query_for(QueryLoader::CATEGORIES);
        $parser = Parser::get_parser_for(CategoryParser::class);
        $vars = [
            'children' => $includeChildren,
            'id' => $id
        ];
        return $parser->parse($this->call(
            $query,
            $vars,
            'categories'
        ))->first();
    }

    public function getTags($id = null, $filterEnabled = null, $all = false)
    {
        $query = QueryLoader::get_query_for(QueryLoader::TAGS);
        $parser = Parser::get_parser_for(TagParser::class);
        $vars = [];
        if ($id) {
            $vars['id'] = $id;
        }
        if ($all) {
            $vars['all'] = $all;
        }
        if (!is_null($filterEnabled)) {
            $vars['filterEnabled'] = $filterEnabled;
        }

        return $parser->parse($this->call(
            $query,
            $vars,
            'tags'
        ));
    }

    public function getLocations($id = null, $ids = null, $parentIds = null, $all = null)
    {
        $query = QueryLoader::get_query_for(QueryLoader::LOCATIONS);
        $vars = [];
        if (!is_null($id)) {
            $vars['id'] = $id;
        }
        if (!is_null($ids)) {
            $vars['ids'] = $ids;
        }
        if (!is_null($parentIds)) {
            $vars['parentIds'] = $parentIds;
        }
        if (!is_null($all)) {
            $vars['all'] = $all;
        }
        $parser = Parser::get_parser_for(LocationParser::class);
        return $parser->parse($this->call(
            $query,
            $vars,
            'locations'
        ));
    }

    public function getRegions()
    {
        $query = QueryLoader::get_query_for(QueryLoader::REGIONS);
        $parser = Parser::get_parser_for(RegionParser::class);
        return $parser->parse($this->call(
            $query,
            [],
            'regions'
        ));
    }

//    public function fulltextSearchListings($keywords, $offset = null, $limit = 12)
//    {
//        $query = QueryLoader::get_query_for(QueryLoader::LISTINGS_SEARCH);
//        $vars = [
//            'keywords' => $keywords,
//            'offset' => $offset,
//            'limit' => $limit
//        ];
//        $parser = Parser::get_parser_for(PaginatedListingsParser::class);
//        return $parser->parse($this->call(
//            $query,
//            $vars,
//            'fulltextListings'
//        ));
//    }



    public function getEventListings($categories = null, $tags = null, $places = null, $keywords = '', $startDate = null, $endDate = null, $offset = null, $limit = 12, $latitude = null, $longitude = null, $radius = 2000, $exclude = null, $excludeCategories = null)
    {
        return $this->runListingsQuery(
            self::EVENTS,
            $categories,
            $tags,
            $places,
            $keywords,
            $startDate,
            $endDate,
            $offset,
            $limit,
            $latitude,
            $longitude,
            $radius,
            $exclude,
            $excludeCategories
        );

    }

    public function getAllListings($categories = null, $tags = null, $places = null, $keywords = '', $startDate = null, $endDate = null, $offset = null, $limit = 12, $latitude = null, $longitude = null, $radius = 2000, $exclude = null, $excludeCategories = null)
    {
        return $this->runListingsQuery(
            self::ALL,
            $categories,
            $tags,
            $places,
            $keywords,
            $startDate,
            $endDate,
            $offset,
            $limit,
            $latitude,
            $longitude,
            $radius,
            $exclude,
            $excludeCategories
        );

    }

    public function getListings($categories = null, $tags = null, $places = null, $keywords = '', $startDate = null, $endDate = null, $offset = null, $limit = 12, $latitude = null, $longitude = null, $radius = 2000, $exclude = null, $excludeCategories = null)
    {
        return $this->runListingsQuery(
            self::LISTINGS,
            $categories,
            $tags,
            $places,
            $keywords,
            $startDate,
            $endDate,
            $offset,
            $limit,
            $latitude,
            $longitude,
            $radius,
            $exclude,
            $excludeCategories
        );

    }

    public function runListingsQuery($listingType = self::LISTINGS, $categories = null, $tags = null, $places = null, $keywords = '', $startDate = null, $endDate = null, $offset = null, $limit = 12, $latitude = null, $longitude = null, $radius = 2000, $exclude = null, $excludeCategories = null)
    {
        $sortBy = [
            [
                'field' => 'Name',
                'direction' => 'ASC'
            ]
        ];

        $query = QueryLoader::get_query_for(QueryLoader::LISTINGS);
        $vars = [];
        if ($listingType) {
            $vars['listingType'] = $listingType;
        }
        if ($categories) {
            $vars['categories'] = $categories;
        }
        if ($tags) {
            $vars['tags'] = $tags;
        }
        if ($places) {
            $vars['places'] = $places;
        }
        if ($keywords) {
            $vars['keywords'] = $keywords;
        }
        if ($startDate) {
            $vars['startDate'] = $startDate;
        }
        if ($endDate) {
            $vars['endDate'] = $endDate;
        }
        if ($offset) {
            $vars['offset'] = $offset;
        }
        if ($limit) {
            $vars['limit'] = $limit;
        }
        if ($exclude) {
            $vars['exclude'] = $exclude;
        }

        if ($latitude && $longitude) {
            $vars['latitude'] = $latitude;
            $vars['longitude'] = $longitude;
            $vars['radius'] = $radius;
        } else {
            $vars['sortBy'] = $sortBy;
        }

        $parser = Parser::get_parser_for(PaginatedListingsParser::class);
        return $parser->parse($this->call(
            $query,
            $vars,
            'listings'
        ));

    }

    /**
     * Get an unpaginated list of Listings with the given IDs
     *
     * @param array $ids
     * @param string $listingType
     * @return ArrayList
     */
    public function getListingsById($ids, $listingType = null)
    {
        $query = QueryLoader::get_query_for(QueryLoader::LISTINGS_BY_ID);
        $vars = ['ids' => $ids];
        if ($listingType) {
            $vars['listingType'] = $listingType;
        }

        $parser = Parser::get_parser_for(ListingsParser::class);
        return $parser->parse($this->call(
            $query,
            $vars,
            'listingsById'
        ));
    }

    /**
     * Get an unpaginated list of Events with the given IDs
     *
     * @param array $ids
     * @return ArrayList
     */
    public function getEventsById($ids)
    {
        return $this->getListingsById($ids, 'Event');
    }

    public function getUpcomingEvents($limit = 12)
    {
        $query = QueryLoader::get_query_for(QueryLoader::UPCOMING_EVENTS);
        $vars = ['limit' => $limit];
        $parser = Parser::get_parser_for(EventsParser::class);
        return $parser->parse($this->call(
            $query,
            $vars,
            'upcomingEvents'
        ));
    }

    public function getTodaysEvents($limit = 12)
    {
        $query = QueryLoader::get_query_for(QueryLoader::TODAYS_EVENTS);
        $vars = ['limit' => $limit];
        $parser = Parser::get_parser_for(EventsParser::class);
        return $parser->parse($this->call(
            $query,
            $vars,
            'todaysEvents'
        ));
    }

    public function getListing($urlSlug, $stage = self::LIVE)
    {
        $query = QueryLoader::get_query_for(QueryLoader::LISTING);
        $parser = Parser::get_parser_for(ListingParser::class);
        $result = $this->call(
            $query,
            [
                'slug' => $urlSlug,
                'stage' => $stage,
            ],
            'listings'
        );
        if ($result && !empty($result['edges'])) {
            return $parser->parse($result['edges'][0]['node']);
        }
        return null;

    }

    /**
     * @param $key
     */
    public static function set_referrer($referrer)
    {
        self::$referrer = $referrer;
    }

    /**
     * @return null:string
     */
    public static function get_referrer()
    {
        return self::$referrer;
    }

    /**
     * @param $key
     */
    public static function set_key($key)
    {
        self::$key = $key;
    }

    /**
     * @return null:string
     */
    public static function get_key()
    {
        return self::$key;
    }

    /**
     * @param $endPoint
     */
    public static function set_end_point($endPoint)
    {
        self::$end_point = $endPoint;
    }

    /**
     * @return null|string
     */
    public static function get_end_point()
    {
        return self::$end_point;
    }

    public static function set_image_sizes($sizes = [])
    {
        foreach ($sizes as $name => $size) {
            $method = 'set_' . $name;
            if (method_exists(self::class, $method)) {
                call_user_func([
                    self::class,
                    $method
                ], $size);
            }
        }
    }

    public static function set_large_image_width($width)
    {
        self::$large_image_width = $width;
    }

    public static function set_large_image_height($height)
    {
        self::$large_image_height = $height;
    }

    public static function set_large_image_scale_method($scaleMethod)
    {
        self::$large_image_scale_method = $scaleMethod;
    }

    public static function set_medium_image_width($width)
    {
        self::$medium_image_width = $width;

    }

    public static function set_medium_image_height($height)
    {
        self::$medium_image_height = $height;
    }

    public static function set_medium_image_scale_method($scaleMethod)
    {
        self::$medium_image_scale_method = $scaleMethod;
    }

    public static function set_small_image_width($width)
    {
        self::$small_image_width = $width;
    }

    public static function set_small_image_height($height)
    {
        self::$small_image_height = $height;
    }

    public static function set_small_image_scale_method($scaleMethod)
    {
        self::$small_image_scale_method = $scaleMethod;
    }

    public static function set_logo_image_width($width)
    {
        self::$logo_image_width = $width;
    }

    public static function set_logo_image_height($height)
    {
        self::$logo_image_height = $height;
    }

    public static function set_logo_image_scale_method($scaleMethod)
    {
        self::$logo_image_scale_method = $scaleMethod;
    }

    public static function set_gallery_image_width($width)
    {
        self::$gallery_image_width = $width;
    }

    public static function set_gallery_image_height($height)
    {
        self::$gallery_image_height = $height;
    }

    public static function set_gallery_image_scale_method($scaleMethod)
    {
        self::$gallery_image_scale_method = $scaleMethod;
    }

    protected function makeOptions($query, $vars)
    {
        $options = [
            'form_params' => [
                'query' => $query,
                'variables' => json_encode($vars)
            ],
        ];
        $headers = [];
        if (self::get_key()) {
            $headers['Authorization'] = sprintf('Bearer %s', self::get_key());
        }
        if (self::get_referrer()) {
            $headers['Origin'] = self::get_referrer();
        }
        $options['headers'] = $headers;
        return $options;
    }

    public function call($query, $vars, $queryName = null)
    {
        $options = $this->makeOptions($query, $vars);
        $response = $this->client->post(
            self::get_end_point(),
            $options
        );
        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $result = json_decode($response->getBody()->getContents(), true);
        if (!isset($result['data'][$queryName])) {
            return [];
        }
        return $result['data'][$queryName];
    }

    /**
     * @return GuzzleClient
     */
    public function getClient()
    {
        return $this->client;
    }

}