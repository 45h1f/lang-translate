<?php

namespace Ashiful\LangTranslate\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Nwidart\Modules\Facades\Module;


class LangTranslateCommand extends Command
{
    public $code, $module, $allLang = [], $index = 0;

    protected $signature = 'make:translate
                            {code : Language Code}
                            {module? : Module name}';

    protected $description = 'lang file translate';


    public function handle()
    {
        $this->info('Running lang translation ...');


        $this->code = trim($this->argument('code'));
        $this->module = trim($this->argument('module'));


        if (!empty($this->module) && !Module::has($this->module)) {
            $this->error("`{$this->module}` module not exist");
            return false;
        };
        $this->getLangFileList();

        foreach ($this->allLang as $lang) {
            $this->info($lang['name'] . ' File Translating...');
            $translate_file = $this->copy_file_for_translate($lang['path']);
            $this->translateFile($translate_file);
        }

        $this->info('Lang Translate Successfully.');

        return true;
    }

    public function getLangFileList()
    {
        if (!empty($this->module)) {
            $rootPath = base_path("Modules/" . $this->module . "/Resources/lang/en");
        } else {
            $rootPath = base_path("resources/lang/en");
        }
        $this->scanDir($rootPath);
    }


    public function getAllLangFiles($code)
    {

        $rootPath = base_path("resources/lang/en");
        $rootFinalPath = base_path("resources/lang/" . $code);

        $this->scanDir($rootPath, $rootFinalPath);
        $activeModules = Module::allEnabled();
        foreach ($activeModules as $module) {
            $modulePath = base_path("Modules/" . $module . "/Resources/lang/en");
            $moduleFinalPath = base_path("Modules/" . $module . "/Resources/lang/" . $code);
            $this->scanDir($modulePath, $moduleFinalPath);
        }
        return $this->allLang;
    }

    public function scanDir($dirPath)
    {
        if (File::isDirectory($dirPath)) {
            foreach (scandir($dirPath) as $key => $path) {
                if ($key > 1) {
                    $this->allLang[$this->index]['path'] = $dirPath . '/' . $path;
                    $this->allLang[$this->index]['name'] = explode(".", $path)[0];
                    $this->index++;
                }
            }
        }
    }


    public function copy_file_for_translate($file_name)
    {
        try {
            $translated_file = str_replace("/en/", '/' . $this->code . '/', $file_name);
            if (!File::isDirectory(pathinfo($translated_file)['dirname'])) {
                File::makeDirectory(pathinfo($translated_file)['dirname']);
            }
            File::copy($file_name, $translated_file);
            return $translated_file;
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

    }

    public function translateFile($filePath)
    {
        try {
            $fileName = pathinfo($filePath)['filename'];
            if (!empty($this->module)) {
                $fileContents = Lang::get(strtolower($this->module) . '::' . $fileName);
            } else {
                $fileContents = Lang::get($fileName);
            }
            $translatedFileContents = [];
            foreach ($fileContents as $key => $content) {
                $translatedFileContents[$key] = $this->translateAPI($content);
            }

            file_put_contents($filePath, '<?php return ' . var_export($translatedFileContents, true) . ';');
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }


    public function translateAPI($content)
    {
        return $this->callAPI($content, $this->code);
    }


    function callAPI($text, $lang_code)
    {
        $apiKey = config('lang-translate.api_key', null);
        $url = config('lang-translate.url', null);
        try {
            $data = [
                'text' => $text,
                'model_id' => 'en-' . $lang_code
            ];
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url . '/v3/translate?version=2018-05-01');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_USERPWD, 'apikey' . ':' . $apiKey);

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            curl_close($ch);

            return json_decode($result)->translations[0]->translation;
        } catch (\Exception $e) {
            return $text;
        }
    }
}
