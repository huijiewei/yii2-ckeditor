<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-22
 * Time: 11:13
 */

namespace huijiewei\ckeditor;

use yii\web\AssetBundle;

class CKEditorAsset extends AssetBundle
{
    public $sourcePath = '@npm/ckeditor';

    public $js = [
        'ckeditor.js',
    ];
}