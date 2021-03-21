<?php

namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Czim\Filter\Enums\JoinKey;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Str;

/**
 * Simple string comparison for a single column on a translated attribute.
 *
 * This will assume dimsav\laravel-translatable, and assume the given table
 * must be translated to '<table name singular>_translations'. If you want to
 * override this behavior, simply pass in the full translation table name for
 * the translationTable parameter.
 *
 * Standard Laravel conventions are required for this to work, so the
 * translated table somethings.id should be referred to in the foreign key
 * on the translations table as something_translations.something_id
 */
class SimpleTranslatedString implements ParameterFilterInterface
{
    protected const TRANSLATION_TABLE_POSTFIX = '_translations';

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string|null
     */
    protected $translationTable;

    /**
     * @var string|null
     */
    protected $column;

    /**
     * @var bool
     */
    protected $exact;

    /**
     * @var string|null
     */
    protected $locale;


    /**
     * @param string      $table
     * @param string|null $translationTable
     * @param string|null $column           if given, overrules the attribute name
     * @param string|null $locale
     * @param bool        $exact            whether this should not be a loosy comparison
     */
    public function __construct(
        string $table,
        ?string $translationTable = null,
        ?string $column = null,
        ?string $locale = null,
        bool $exact = false
    ) {
        if (empty($translationTable)) {
            $translationTable = Str::singular($table) . self::TRANSLATION_TABLE_POSTFIX;
        }

        if (empty($locale)) {
            $locale = app()->getLocale();
        }

        $this->table            = $table;
        $this->translationTable = $translationTable;
        $this->column           = $column;
        $this->locale           = $locale;
        $this->exact            = $exact;
    }

    /**
     * @param string          $name
     * @param mixed           $value
     * @param EloquentBuilder $query
     * @param FilterInterface $filter
     * @return EloquentBuilder
     */
    public function apply(string $name, $value, $query, FilterInterface $filter)
    {
        $column = $this->translationTable . '.'
            . (! empty($this->column) ? $this->column : $name);

        $operator = '=';

        if (! $this->exact) {
            $operator = 'LIKE';
            $value    = '%' . $value . '%';
        }

        $query->where($this->translationTable . '.locale', $this->locale)
            ->where($column, $operator, $value);


        // add a join for the translations
        $filter->addJoin(
            JoinKey::TRANSLATIONS,
            [
                $this->translationTable,
                $this->translationTable . '.' . Str::singular($this->table) . '_id',
                '=',
                $this->table . '.id',
            ]
        );

        return $query;
    }
}
