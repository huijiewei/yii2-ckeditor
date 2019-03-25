<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-22
 * Time: 11:14
 */

namespace huijiewei\ckeditor;

use huijiewei\upload\BaseUpload;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\InputWidget;

class CKEditorWidget extends InputWidget
{
    /* @var $uploadDriver BaseUpload */
    public $uploadDriver = 'upload';

    public $clientOptions = [];

    public $uploadFileSize = 1024 * 1024;
    public $uploadFileTypes = ['jpg', 'jpeg', 'gif', 'png', 'doc', 'docx', 'zip', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf'];

    public function init()
    {
        parent::init();

        $this->initializeUploadDriver();

        $fileUploadBuilds = $this->uploadDriver->build($this->uploadFileSize, $this->uploadFileTypes);

        $this->options = ArrayHelper::merge([
            'class' => 'form-control',
        ], $this->options);

        $this->clientOptions = ArrayHelper::merge([
            'filebrowserUploadUrl' => $fileUploadBuilds['url'],
            'filebrowserImageUploadUrl' => $fileUploadBuilds['url'],
            'allowedContent' => true,
            'removeButtons' => 'About',
        ], $this->clientOptions);

        CKEditorAsset::register($this->getView());

        $this->registerJavascript($fileUploadBuilds);
    }

    private function initializeUploadDriver()
    {
        if (is_string($this->uploadDriver)) {
            $this->uploadDriver = \Yii::$app->get($this->uploadDriver);
        }
    }

    public function registerJavascript($fileUploadBuilds)
    {
        $clientOptions = Json::encode($this->clientOptions);

        $uploadHeaders = Json::encode($fileUploadBuilds['headers']);
        $uploadFormData = Json::encode($fileUploadBuilds['params']);
        $uploadParamName = $fileUploadBuilds['paramName'];
        $uploadResponseType = $fileUploadBuilds['dataType'];
        $uploadResponseParse = $fileUploadBuilds['responseParse'];

        $js = <<<EOD
        $(document).ready(function () {
            var uploadResponseParse = $uploadResponseParse;
            
            var editor = CKEDITOR.replace('{$this->id}', $clientOptions);
            
            var ckeditor = CKEDITOR.instances['{$this->id}'];
            
            ckeditor.on('change', function(){
                this.updateElement();
            });
            
            ckeditor.on('fileUploadRequest',function(evt) {
                var uploadHeaders = $uploadHeaders;
                var uploadFormData = $uploadFormData;
                var uploadResponseType = '$uploadResponseType';
                
                var fileLoader = evt.data.fileLoader;
               
                for (var h in uploadHeaders) {
                    fileLoader.xhr.setRequestHeader(h, uploadHeaders[h]);
                }
                
                fileLoader.xhr.responseType = uploadResponseType == 'xml' ? 'document' : uploadResponseType;
               
                var formData = new FormData();
                
                for (var k in uploadFormData) {
                    if (uploadFormData[k].toString().indexOf('\${filename}') !== -1) {
                        var randomFileName = Math.random().toString(36).slice(-5) + '_' + fileLoader.fileName;
                        
                        formData.append(k, uploadFormData[k].toString().replace('\${filename}', randomFileName));
                    } else {
                        formData.append(k, uploadFormData[k]);
                    }
                }
                
                formData.append('$uploadParamName', fileLoader.file);
                
                fileLoader.xhr.send(formData);
                
                evt.stop();
            });
            
            editor.on( 'fileUploadResponse', function( evt ) {
				evt.stop();
				
				var data = evt.data;
                var xhr = data.fileLoader.xhr;
                
                data.url = uploadResponseParse(xhr.response);
            });
        });
EOD;

        $this->getView()->registerJs($js, View::POS_END);
    }

    public function run()
    {
        parent::run();

        if ($this->hasModel()) {
            return Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            return Html::textarea($this->name, $this->value, $this->options);
        }
    }
}