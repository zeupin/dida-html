<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Html;

class ActiveElement
{
    const VERSION = '20171122';

    protected $bool_prop_list = [
        'disabled'       => null,
        'readonly'       => null,
        'required'       => null,
        'hidden'         => null,
        'checked'        => null,
        'selected'       => null,
        'autofocus'      => null,
        'multiple'       => null,
        'formnovalidate' => null,
    ];

    protected $autoclose_element_list = [
        'input' => null,
        'br'    => null,
        'hr'    => null,
    ];

    protected $props = [
        'type'  => null,
        'id'    => null,
        'class' => null,
        'name'  => null,
        'value' => null,
    ];

    protected $tag = '';

    protected $autoclose = false;

    protected $opentag = '';

    protected $innerHTML = '';

    public $belongsTo = null;

    protected $wrapper = null;

    protected $before = null;

    protected $after = null;

    protected $children = [];


    public function __construct($tag = null, $more = null)
    {
        if (!is_null($tag)) {
            $this->setTag($tag, $more);
        }
    }


    public function setTag($tag = null, $more = null)
    {
        if (is_null($tag)) {
            $this->tag = null;
            $this->opentag = '';
            $this->autoclose = false;
            return $this;
        }

        if (is_string($tag)) {
            $tag = trim($tag);

            if ($tag === '') {
                $this->tag = null;
                $this->opentag = '';
                $this->autoclose = false;
                return $this;
            }

            $this->tag = $tag;
            if ($more) {
                $this->opentag = $this->tag . ' ' . trim($more);
            } else {
                $this->opentag = $this->tag;
            }

            $this->autoclose = array_key_exists($tag, $this->autoclose_element_list);

            return $this;
        }

        throw new HtmlException('', HtmlException::INVALID_TAG_TYPE);
    }


    public function setID($id)
    {
        $this->props['id'] = $id;
        return $this;
    }


    public function setName($name)
    {
        $this->props['name'] = $name;
        return $this;
    }


    public function setClass($class)
    {
        $this->props['class'] = $class;
        return $this;
    }


    public function setStyle($style)
    {
        $this->props['style'] = $style;
        return $this;
    }


    public function getID()
    {
        return $this->props['id'];
    }


    public function getName()
    {
        return $this->props['name'];
    }


    public function getClass()
    {
        return $this->props['class'];
    }


    public function getStyle()
    {
        return (isset($this->props['style'])) ? $this->props['style'] : null;
    }


    public function setProp($name, $value)
    {
        if (!is_string($name)) {
            throw new HtmlException($name, HtmlException::INVALID_PROPERTY_NAME);
        }

        if (!is_scalar($value) && !is_null($value)) {
            throw new HtmlException($name, HtmlException::INVALID_PROPERTY_VALUE);
        }

        $name = strtolower($name);

        if (is_null($value)) {
            $this->removeProp($name);
            return $this;
        }

        if (array_key_exists($name, $this->bool_prop_list)) {
            if ($value) {
                $this->props[$name] = true;
            } else {
                unset($this->props[$name]);
            }
            return $this;
        }

        $this->props[$name] = $value;

        return $this;
    }


    public function getProp($name)
    {
        if (!is_string($name)) {
            throw new HtmlException($name, HtmlException::INVALID_PROPERTY_NAME);
        }

        $name = strtolower($name);

        switch ($name) {
            case 'type':
            case 'id':
            case 'class':
            case 'name':
            case 'value':
                return $this->props[$name];
                break;
        }

        if (array_key_exists($name, $this->props)) {
            return $this->props[$name];
        } else {
            return null;
        }
    }


    public function removeProp($name)
    {
        $name = strtolower($name);

        switch ($name) {
            case 'type':
            case 'id':
            case 'class':
            case 'name':
            case 'value':
                $this->props[$name] = null;
                break;
            default:
                unset($this->props[$name]);
        }

        return $this;
    }


    public function setInnerHTML($html)
    {
        $this->innerHTML = $html;
        $this->children = [];
        return $this;
    }


    public function getInnerHTML()
    {
        return $this->innerHTML . $this->buildChildren();
    }


    public function &wrap($tag = 'div')
    {
        $this->wrapper = new \Dida\HTML\ActiveElement($tag);
        return $this->wrapper;
    }


    protected function &addNew(&$element = null)
    {
        if (is_null($element) || is_string($element)) {
            $ele = new \Dida\HTML\ActiveElement($element);
        } elseif (is_object($element) && is_a($element, __CLASS__)) {
            $ele = &$element;
        } else {
            throw new HtmlException(null, HtmlException::INVALID_ELEMENT_TYPE);
        }

        $ele->belongsTo = &$this;

        return $ele;
    }


    public function &addBefore($element = null)
    {
        $ele = $this->addNew($element);
        $this->before = &$ele;
        return $ele;
    }


    public function &addAfter($element = null)
    {
        $ele = $this->addNew($element);
        $this->after = &$ele;
        return $ele;
    }


    public function &addChild($element = null)
    {
        $ele = $this->addNew($element);
        $this->children[] = &$ele;
        return $ele;
    }


    protected function buildProps()
    {
        $output = [];

        foreach ($this->props as $name => $value) {
            if (is_null($value)) {
            } elseif ($value === true) {
                $output[] = ' ' . htmlspecialchars($name);
            } elseif ($name === 'style') {
                $output[] = ' style' . '="' . htmlspecialchars($string, ENT_COMPAT | ENT_HTML5) . '"';
            } else {
                $output[] = ' ' . htmlspecialchars($name) . '="' . htmlspecialchars($value) . '"';
            }
        }

        return implode('', $output);
    }


    protected function buildChildren()
    {
        if (empty($this->children)) {
            return '';
        }

        $output = [];
        foreach ($this->children as $element) {
            if ($element->belongsTo === $this) {
                $output[] = $element->build();
            }
        }
        return implode('', $output);
    }


    protected function buildMe()
    {
        if (!$this->tag) {
            return $this->getInnerHTML();
        }

        if ($this->autoclose) {
            return "<" . $this->opentag . $this->buildProps() . '>';
        }

        return "<" . $this->opentag . $this->buildProps() . '>' . $this->getInnerHTML() . "</{$this->tag}>";
    }


    public function build()
    {
        $output = [];

        if (!is_null($this->before) && ($this->belongsTo === $this)) {
            $output[] = $this->before->build();
        }

        $output[] = $this->buildMe();

        if (!is_null($this->after) && ($this->belongsTo === $this)) {
            $output[] = $this->after->build();
        }

        $result = implode('', $output);

        if (is_null($this->wrapper)) {
            return $result;
        } else {
            $this->wrapper->innerHTML = &$result;
            return $this->wrapper->build();
        }
    }
}
