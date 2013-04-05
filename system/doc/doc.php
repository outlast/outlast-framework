<?php
/**
 * This file helps IDEs autocomplete stuff within this plugin. It is never actually used.
 **/
die("This file is for documentation.");

/**
 * @method static zajDb boolean
 * @method static zajDb date
 * @method static zajDb files
 * @method static zajDb float
 * @method static zajDb id
 * @method static zajDb integer
 * @method static zajDb locale
 * @method static zajDb locales
 * @method static zajDb manytomany
 * @method static zajDb manytoone
 * @method static zajDbMap map
 * @method static zajDb name
 * @method static zajDb onetomany
 * @method static zajDb ordernum
 * @method static zajDb password
 * @method static zajDb photos
 * @method static zajDb rating
 * @method static zajDb richtext
 * @method static zajDb select
 * @method static zajDb serialized
 * @method static zajDb text
 * @method static zajDb textarea
 * @method static zajDb textbox
 * @method static zajDb time
 * @method static zajDb timestamp
 * @method static zajDb tinymce
 * @method static zajDb year
 **/
class zajDb{}

/**
 * Class zajDbMap
 * @method zajDbMap geolocation
 */
class zajDbMap extends zajDb{}



/**
 * Adds some dynamic properties
 * @property string $class The class of the parent.
 * @property string $parent The id of the parent.
 * @property string $field The field name of the parent.
 * @property string $name The file name.
 * @property boolean $timepath If the new time-based path is used.
 * @property integer $time_create
 * @property string $extension
 * @property string $imagetype Can be IMAGETYPE_PNG, IMAGETYPE_GIF, or IMAGETYPE_JPG constant.
 * @property string $status
 * @property string $original Depricated.
 * @property string $description Description.
 **/
class zajDataPhoto extends zajData{}

/**
 * Adds some dynamic properties
 * @property float $lat
 * @property float $lng
 **/
class zajDataMap {}
