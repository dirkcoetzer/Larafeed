<?php namespace DotZecker\Larafeed;

use Carbon\Carbon;

class Entry {

    public $title;

    public $link;

    public $author;

    public $pubDate;

    public $update;

    public $summary;

    public $content;


    public function __construct($title = null, $link = null, $author = null, $pubDate = null, $content = null, $contentType = 'atom')
    {
        if ( ! is_null($title))   $this->title   = $title;
        if ( ! is_null($link))    $this->link    = $link;
        if ( ! is_null($author))  $this->author  = $author;
        if ( ! is_null($pubDate)) {
            $method = 'to' . strtolower($contentType) . 'String';
            $this->pubDate = Carbon::parse($pubDate)->{$method}();
        }
        if ( ! is_null($content)) $this->content = $content;
    }

    public function autocomplete($contentType)
    {
        if (is_null($this->pubDate)) {
            $method = 'to' . strtolower($contentType) . 'String';
            $this->pubDate = Carbon::parse('now')->{$method}();
        }

        if (is_null($this->summary)) {
            $summary = strip_tags($this->content);
            $this->summary = substr($summary, 0, 144) . '...';
        }

        $this->title = strip_tags($this->title);

        return $this;
    }
}