<?php

namespace plugin\article\app\control\controller;

use app\expose\build\builder\FormBuilder;
use app\expose\enum\SubmitEvent;
use plugin\article\app\Basic;
use plugin\article\app\model\PluginArticle;
use plugin\article\app\model\PluginArticleContent;
use support\Request;
use app\expose\helper\Config;

class AgreementController extends Basic
{

    public function privacy(Request $request)
    {
        $alias = 'privacy';
        if ($request->method() === 'POST') {
            return $this->saveAgreement($request->post(), $alias, $request->channels_uid);
        }

        return $this->showAgreementForm($alias);
    }
    public function user(Request $request)
    {
        $alias = 'user';
        if ($request->method() === 'POST') {
            return $this->saveAgreement($request->post(), $alias, $request->channels_uid);
        }

        return $this->showAgreementForm($alias);
    }
    public function guide(Request $request)
    {
        $alias = 'guide';
        if ($request->method() === 'POST') {
            return $this->saveAgreement($request->post(), $alias, $request->channels_uid);
        }
        return $this->showAgreementForm($alias);
    }
    public function help(Request $request)
    {
        $alias = 'help';
        if ($request->method() === 'POST') {
            return $this->saveAgreement($request->post(), $alias, $request->channels_uid);
        }

        return $this->showAgreementForm($alias);
    }
    public function points(Request $request)
    {
        $alias = 'points';
        if ($request->method() === 'POST') {
            return $this->saveAgreement($request->post(), $alias, $request->channels_uid);
        }
        return $this->showAgreementForm($alias);
    }
    public function terms(Request $request)
    {
        $alias = 'terms';
        if ($request->method() === 'POST') {
            return $this->saveAgreement($request->post(), $alias, $request->channels_uid);
        }

        return $this->showAgreementForm($alias);
    }
    public function about(Request $request)
    {
        $alias = 'about';
        if ($request->method() === 'POST') {
            return $this->saveAgreement($request->post(), $alias, $request->channels_uid);
        }

        return $this->showAgreementForm($alias);
    }
    public function cloning(Request $request)
    {
        $alias = 'cloning';
        if ($request->method() === 'POST') {
            return $this->saveAgreement($request->post(), $alias, $request->channels_uid);
        }
        return $this->showAgreementForm($alias);
    }

    protected function saveAgreement(array $data, string $alias, $channels_uid)
    {
        $article = PluginArticle::where(['alias' => $alias])->find();
        if (!$article) {
            $article = new PluginArticle;
            $article->alias = $alias;
            $article->channels_uid = $channels_uid;
        }
        $article->title = $data['title'];
        $article->save();

        $content = PluginArticleContent::where(['article_id' => $article->id])->find();
        if (!$content) {
            $content = new PluginArticleContent;
            $content->article_id = $article->id;
        }
        $content->content = $data['content'];
        $result = $content->save();
        $this->cacheHtml($article->id);
        if ($result) {
            return $this->success('保存成功1');
        } else {
            return $this->fail('保存失败');
        }
    }

    protected function showAgreementForm(string $alias)
    {
        $builder = $this->getFormBuilder($alias);

        $article = PluginArticle::where('alias', $alias)->find();
        if ($article) {
            $content = PluginArticleContent::where('article_id', $article->id)->find();
            $article->content = $content->content ?? '';
            $builder->setData($article->toArray());
        }

        return $this->resData($builder);
    }

    protected function getFormBuilder(string $alias)
    {
        $builder = new FormBuilder(null, null, ['size' => 'large','submitEvent' => SubmitEvent::SILENT]);

        $builder->add('title', '标题', 'input', '', [
            'required' => true,
            'props' => [
                'maxlength' => 100,
                'show-word-limit' => true
            ]
        ]);

        $builder->add('alias', '名称', 'input', $alias, [
            'props' => [
                'disabled' => true
            ]
        ]);

        $builder->add('content', '内容', 'wangeditor', '', [
            'required' => true,
            'props' => [
                'class' => 'vh-60'
            ]
        ]);

        return $builder;
    }



    public function cacheHtml($id)
    {
        $PluginArticle = PluginArticle::alias('a')->where(['a.id' => $id])->join('plugin_article_content c', 'c.article_id=a.id')->field('a.*,c.content')->find();
        $config = new Config('basic', '');
        if (!$PluginArticle->source) {
            $PluginArticle->source = $config['web_name'];
        }
        if ($config['logo_use'] == 'image') {
            $PluginArticle->web_logo = $config['web_logo_light'];
        } else {
            $PluginArticle->web_logo = '/vite.svg';
        }
        $PluginArticle->web_title = "{$PluginArticle->title} - {$config['web_title']}";
        $html = view(base_path('plugin/article/public/template.html'), $PluginArticle->toArray(), null, '')->rawBody();
        $path = public_path('article/' . $id . '.html');
        file_put_contents($path, $html);
        if ($PluginArticle->alias) {
            $path = public_path('article/' . $PluginArticle->alias . '.html');
            file_put_contents($path, $html);
        }
    }
}
