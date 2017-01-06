<?php

class DiffContext
{
    protected
        $context = true,
        $ignore_whitespace = false,
        $ignore_formatting = false,
        $renames = false,
        $skip_suppress = false,
        $show_hidden = true;

    public function setSkipSuppress($skip_suppress)
    {
        $this->skip_suppress = $skip_suppress;
        return $this;
    }

    public function getSkipSuppress()
    {
        return $this->skip_suppress;
    }

    public function setRenames($renames)
    {
        $this->renames = $renames;
        return $this;
    }

    public function getRenames()
    {
        return $this->renames;
    }

    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setIgnoreFormatting($ignore_formatting)
    {
        $this->ignore_formatting = $ignore_formatting;
        return $this;
    }

    public function getIgnoreFormatting()
    {
        return $this->ignore_formatting;
    }

    public function setIgnoreWhitespace($ignore_whitespace)
    {
        $this->ignore_whitespace = $ignore_whitespace;
        return $this;
    }

    public function getIgnoreWhitespace()
    {
        return $this->ignore_whitespace;
    }

    public function setShowHidden($show_hidden)
    {
        $this->show_hidden = $show_hidden;
        return $this;
    }

    public function getShowHidden()
    {
        return $this->show_hidden;
    }
}
