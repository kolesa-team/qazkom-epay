<?php
namespace Epay;

/**
 * Парсер XML.
 */
class Xml
{
    /**
     * Парсер.
     *
     * @var resource
     */
    protected $parser;

    /**
     * Результирующий массив с данными.
     *
     * @var array
     */
    protected $result = array();

    /**
     * Текущий обрабатываемый тэг.
     *
     * @var string
     */
    protected $currentTag;

    /**
     * Конструктор.
     */
    public function __construct()
    {
        $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($this->parser, 'openTag', 'closeTag');
        xml_set_character_data_handler($this->parser, 'cdata');
    }

    /**
     * Разбирает XML-строку в массив.
     *
     * @param  string $data
     * @return array
     */
    public function parse($data)
    {
        xml_parse($this->parser, $data);
        ksort($this->result, SORT_STRING);

        return $this->result;
    }

    /**
     * Парсер открывающего тэга.
     *
     * @param $parser
     * @param $tag
     * @param $attributes
     */
    public function openTag($parser, $tag, $attributes)
    {
        $this->currentTag            = $tag;
        $this->result['TAG_' . $tag] = $tag;

        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $this->result[$tag . '_' . $key] = $value;
            }
        }
    }

    /**
     * Парсер закрывающего тэга.
     *
     * @param $parser
     * @param $tag
     */
    public function closeTag($parser, $tag)
    {
    }

    /**
     * Парсер символьных данных.
     *
     * @param $parser
     * @param $cdata
     */
    public function cdata($parser, $cdata)
    {
        $this->result[$this->currentTag . '_CHARDATA'] = $cdata;
    }
}
