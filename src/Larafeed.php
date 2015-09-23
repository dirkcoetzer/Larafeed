<?php namespace dirkcoetzer\Larafeed;

use URL;
use View;
use Config;
use Response;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use dirkcoetzer\Larafeed\Exceptions\LarafeedException;

class Larafeed
{
    public $charset = 'utf-8';

    public $lang;

    public $title;

    public $description; // Subtitle

    public $pubDate;

    public $link;

    public $feedLink;

    public $logo;

    public $icon;

    public $rights;

    public $authors;

    public $entries;

    protected $contentTypes = array(
        'atom' => 'application/atom+xml',
        'rss'  => 'application/rss+xml'
    );

    public $format = 'atom';

    /**
     * Set the format, fill attributes and instance authors and entries
     * @param string $format
     */
    public function __construct($format = null, array $data = array())
    {
        if ($format == 'rss') {
            $this->format = $format;
        }

        foreach ($data as $attribute => $value) {
            $this->{$attribute} = $value;
        }

        $this->authors = new Collection();
        $this->entries = new Collection();
    }

    /**
     * Return new instance of Larafeed
     * @param  string $format
     * @return Larafeed
     */
    public function make($format = null, array $data = array())
    {
        return new Larafeed($format, $data);
    }

    /**
     * Return new instance of an Entry
     * @param array $data
     */
    public function Entry(array $data = array())
    {
        return new Entry($data);
    }

    /**
     * Prepare and push the entry to the feed (If it is valid)
     * @param Entry $entry
     */
    public function setEntry(Entry $entry)
    {
        $entry->format = $this->format;
        $entry->prepare();

        if ($entry->isValid()) {
            $this->entries->push($entry);
        }
    }

    /**
     * Create a new instanc of Entry and try to set it
     * @param array $data
     */
    public function addEntry(array $data = array())
    {
        $entry = new Entry($data);

        $this->setEntry($entry);
    }

    /**
     * Add an Author to the feed
     * @param mixed $author It can be an array with name, email and uri,
     *        or just and string with the name.
     */
    public function addAuthor($author)
    {
        if ( ! is_array($author)) {
            $author = array('name' => $author);
        }

        $this->authors->push((object) $author);
    }

    /**
     * Prepare the feed and if it's valid, renderize it
     * @return Response
     */
    public function render()
    {
        $this->prepare();

        $view = View::make("larafeed::{$this->format}", array(
            'feed' => $this
        ));

        // Launch the Atom/RSS view, with 200 status
        return Response::make($view, 200, array(
            'Content-Type' => "{$this->getContentType()}; charset={$this->charset}"
        ));

    }

    /**
     * Validate, autofill and sanitize the entry
     * @return void
     */
    protected function prepare()
    {
        // The date format method to use with Carbon to convert the dates
        $dateFormatMethod = 'to' . strtolower($this->format) . 'String';

        // Set the good date format to the publication date
        if ( ! is_null($this->pubDate)) {
            $this->pubDate = Carbon::parse($this->pubDate)->{$dateFormatMethod}();
        }

        // Fill the empty attributes
        $this->autoFill();

        // We ensure that it's plain text
        $this->title = strip_tags($this->title);
        $this->description = strip_tags($this->description);

        // Feed validation
        $this->validate();
    }

    /**
     * Fill the attributes that can be autogenerated
     * @return void
     */
    protected function autoFill()
    {
        // The date format method to use with Carbon to convert the dates
        $dateFormatMethod = 'to' . strtolower($this->format) . 'String';

        // Set the 'now' date
        if (is_null($this->pubDate)) {
            $this->pubDate = Carbon::parse('now')->{$dateFormatMethod}();
        }

        // Set laravel's default lang
        if (is_null($this->lang)) {
            $this->lang = Config::get('app.locale');
        }

        // Set url to homepage (Or whatever it is there)
        if (is_null($this->link)) {
            $this->link = URL::to('/');
        }

        // Set the feed url
        if (is_null($this->feedLink)) {
            $this->feedLink = URL::full();
        }

    }

    /**
     * Get the content type
     * @return string
     */
    public function getContentType()
    {
        return $this->contentTypes[$this->format];
    }

    /**
     * Validate the entry
     * @return boolean
     */
    public function validate()
    {
        $data = get_object_vars($this);

        $rules = array(
            'format'      => 'required|in:atom,rss',
            'charset'     => 'required',
            'lang'        => 'required',
            'title'       => 'required',
            'description' => 'required',
            'pubDate'     => 'required|date',
            'feedLink'    => 'required|url',
            'link'        => 'required|url'
        );

        if (isset($this->logo)) {
            $rules['logo'] = 'url';
        }

        if (isset($this->icon)) {
            $rules['icon'] = 'url';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new LarafeedException($validator->errors()->first());
        }

        return true;
    }

}
