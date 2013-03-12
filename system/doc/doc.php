<?php
/**
 * This file helps IDEs autocomplete stuff within this plugin. It is never actually used.
 **/
die("This file is for documentation.");

/**
 * @method static boolean
 * @method static date
 * @method static files
 * @method static float
 * @method static id
 * @method static integer
 * @method static locale
 * @method static locales
 * @method static manytomany
 * @method static manytoone
 * @method static map
 * @method static name
 * @method static onetomany
 * @method static ordernum
 * @method static password
 * @method static photos
 * @method static rating
 * @method static richtext
 * @method static select
 * @method static serialized
 * @method static text
 * @method static textarea
 * @method static textbox
 * @method static time
 * @method static timestamp
 * @method static tinymce
 * @method static year
 **/
class zajDb{}

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
