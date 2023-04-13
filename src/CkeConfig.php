<?php

namespace craft\ckeditor;

use Craft;
use craft\base\Model;
use craft\helpers\Json;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;
use yii\validators\Validator;

/**
 * CKEditor Config model
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CkeConfig extends Model
{
    /**
     * @var string|null The configuration UUID
     */
    public ?string $uid = null;

    /**
     * @var string|null The configuration name
     */
    public ?string $name = null;

    /**
     * @var string[] The toolbar configuration
     */
    public array $toolbar = ['heading', '|', 'bold', 'italic', 'link'];

    /**
     * @var string|null JSON code that defines additional CKEditor config properties as an object
     * @since 3.1.0
     */
    public ?string $json = null;

    /**
     * @var string|null JavaScript code that returns additional CKEditor config properties as an object
     */
    public ?string $js = null;

    /**
     * @var string|null CSS styles that should be registered for the field.
     */
    public ?string $css = null;

    public function __construct($config = [])
    {
        // Only use `json` or `js`, not both
        if (!empty($config['json'])) {
            unset($config['js']);
            $config['json'] = trim($config['json']);
            if ($config['json'] === '' || preg_match('/^\{\s*\}$/', $config['json'])) {
                unset($config['json']);
            }
        } else {
            unset($config['json']);
            if (isset($config['js'])) {
                $config['js'] = trim($config['js']);
                if ($config['js'] === '' || preg_match('/^return\s*\{\s*\}$/', $config['js'])) {
                    unset($config['js']);
                }
            }
        }

        if (isset($config['css'])) {
            $config['css'] = trim($config['css']);
            if ($config['css'] === '') {
                unset($config['css']);
            }
        }

        parent::__construct($config);
    }

    public function attributeLabels(): array
    {
        return [
            'name' => Craft::t('app', 'Name'),
            'toolbar' => Craft::t('ckeditor', 'Toolbar'),
            'json' => Craft::t('ckeditor', 'Config Options'),
            'js' => Craft::t('ckeditor', 'Config Options'),
            'css' => Craft::t('ckeditor', 'Custom Styles'),
        ];
    }

    protected function defineRules(): array
    {
        return [
            ['name', 'trim'],
            [['name', 'toolbar'], 'required'],
            ['name', function(string $attribute, ?array $params, Validator $validator) {
                $duplicateName = Collection::make(Plugin::getInstance()->getCkeConfigs()->getAll())
                    ->contains(fn(CkeConfig $ckeConfig) => (
                        $ckeConfig->name === $this->name &&
                        $ckeConfig->uid !== $this->uid
                    ));
                if ($duplicateName) {
                    $validator->addError($this, $attribute, Craft::t('yii', '{attribute} "{value}" has already been taken.'));
                }
            }],
            ['json', function(string $attribute, ?arrray $params, Validator $validator) {
                try {
                    Json::decode($this->json);
                } catch (InvalidArgumentException) {
                    $validator->addError($this, $attribute, Craft::t('ckeditor', '{attribute} isn’t valid JSON.'));
                }
            }],
        ];
    }
}
