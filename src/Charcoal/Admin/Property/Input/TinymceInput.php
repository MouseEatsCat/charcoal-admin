<?php

namespace Charcoal\Admin\Property\Input;

use \InvalidArgumentException;

// Dependency from 'charcoal-translation'
use \Charcoal\Translation\TranslationString;

// Intra-module (`charcoal-admin`) dependencies
use \Charcoal\Admin\Property\AbstractPropertyInput;

/**
 * TinyMCE Rich-Text Input Property
 */
class TinymceInput extends AbstractPropertyInput
{
    /**
     * The TinyMCE editor settigns.
     *
     * @var array
     */
    private $editorOptions;

    /**
     * Label for the file picker dialog.
     *
     * @var TranslationString|string
     */
    private $dialogTitle;

    /**
     * Set the editor's options.
     *
     * This method overwrites existing helpers.
     *
     * @param array $opts The editor options.
     * @return Tinymce Chainable
     */
    public function setEditorOptions(array $opts)
    {
        $this->editorOptions = $opts;

        return $this;
    }

    /**
     * Merge (replacing or adding) editor options.
     *
     * @param array $opts The editor options.
     * @return Tinymce Chainable
     */
    public function mergeEditorOptions(array $opts)
    {
        $this->editorOptions = array_merge($this->editorOptions, $opts);

        return $this;
    }

    /**
     * Add (or replace) an editor option.
     *
     * @param string $optIdent The setting to add/replace.
     * @param mixed  $optVal   The setting's value to apply.
     * @throws InvalidArgumentException If the identifier is not a string.
     * @return Tinymce Chainable
     */
    public function addEditorOption($optIdent, $optVal)
    {
        if (!is_string($optIdent)) {
            throw new InvalidArgumentException(
                'Option identifier must be a string.'
            );
        }

        // Make sure default options are loaded.
        if ($this->editorOptions === null) {
            $this->editorOptions();
        }

        $this->editorOptions[$optIdent] = $optVal;

        return $this;
    }

    /**
     * Retrieve the editor's options.
     *
     * @return array
     */
    public function editorOptions()
    {
        if ($this->editorOptions === null) {
            $this->setEditorOptions($this->defaultEditorOptions());
        }

        return $this->editorOptions;
    }

    /**
     * Retrieve the default editor options.
     *
     * @return array
     */
    public function defaultEditorOptions()
    {
        $metadata = $this->metadata();

        if (isset($metadata['data']['editor_options'])) {
            return $metadata['data']['editor_options'];
        }

        return [];
    }

    /**
     * Retrieve the editor's options as a JSON string.
     *
     * @return string Returns data serialized with {@see json_encode()}.
     */
    public function editorOptionsAsJson()
    {
        return json_encode($this->editorOptions());
    }

    /**
     * Set the title for the file picker dialog.
     *
     * @param  string|string[] $title The dialog title.
     * @return self
     */
    public function setDialogTitle($title)
    {
        if (TranslationString::isTranslatable($title)) {
            $this->dialogTitle = new TranslationString($title);
        } else {
            $this->dialogTitle = null;
        }

        return $this;
    }

    /**
     * Retrieve the default title for the file picker dialog.
     *
     * @return string[]
     */
    protected function defaultDialogTitle()
    {
        return [
            'en' => 'Media Library',
            'fr' => 'Bibliothèque de médias'
        ];
    }

    /**
     * Retrieve the title for the file picker dialog.
     *
     * @return TranslationString|string|null
     */
    public function dialogTitle()
    {
        if ($this->dialogTitle === null) {
            $this->setDialogTitle($this->defaultDialogTitle());
        }

        return $this->dialogTitle;
    }
}
