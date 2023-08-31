<?php


namespace app\author\controller;

use app\model\ArticleArticle;
use app\model\ArticleChapter;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\facade\App;
use think\facade\View;

class Chapters extends Base
{
    protected $chapterModel;

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->chapterModel = app('chapterModel');
    }

    public function list() {
        $articleid = input('articleid');
        View::assign('articleid', $articleid);
        return view();
    }

    public function getlist() {
        $articleid = input('articleid');
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $data = ArticleChapter::where('articleid','=',$articleid)->order('chapterorder', 'desc');
        $count = $data->count();
        $chapters = $data->limit(($page - 1) * $limit, $limit)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $chapters
        ]);
    }

    public function create() {
        $data = request()->param();
        if (request()->isPost()) {
            $content = $data['cover'];
            $chapter = new ArticleChapter();
            $chapter->chaptername = $data['chapter_name'];
            $chapter->chapterorder = $data['chapter_order'];
            $chapter->preface = substr($content,0,20);
            $chapter->notice = substr($content,0,20);
            $chapter->foreword = substr($content,0,20);
            $chapter->display = 1;
            $chapter->postdate = time();
            $chapter->lastupdate = time();

            $result= $chapter->save($data);

            if ($result){
                $param = [
                    "articleid" => $data["articleid"],
                    "lastupdate" => time()
                ];
                $result2 = ArticleArticle::update($param);
                if ($result2) {
                    $bigId = floor((double)($chapter['articleid'] / 1000));
                    $dir = App::getRootPath().'/public/files/' . sprintf('/article/txt/%s/%s/',$bigId, $chapter['articleid']);
                    if (!is_dir($dir))
                    {
                        mkdir($dir, 0777, true);
                    }
                    $filename = App::getRootPath().'/public/files/' . sprintf('/article/txt/%s/%s/%ss.txt',
                            $bigId, $chapter['articleid'], $chapter['articleid']);
                    file_put_contents($filename, $content);
                    return json(['err' =>0,'msg'=>'添加成功']);
                } else {
                    return json(['err' =>1,'msg'=>'添加失败']);
                }
            }else{
                return json(['err' =>1,'msg'=>'添加失败']);
            }
        }
        $articleid = $data['articleid'];
        $lastChapterOrder = 0;
        $lastChapter = ArticleChapter::where('articleid','=',$articleid)
            ->order('chapterid','desc')->limit(1)->find();
        if ($lastChapter){
            $lastChapterOrder = $lastChapter->chapterorder;
        }
        View::assign([
            'articleid' => $articleid,
            'order' => $lastChapterOrder + 1,
        ]);
        return view();
    }

    public function edit() {
        $id = input('articleid');
        try {
            $chapter = ArticleChapter::findOrFail($id);
            if (request()->isPost()) {
                $chapter->chaptername = input('chapter_name');
                $chapter->chapterorder = input('chapter_order');
                $result = $chapter->save();
                if ($result) {
                    return json(['err' =>0,'msg'=>'编辑成功']);
                } else {
                    return json(['err' =>1,'msg'=>'编辑失败']);
                }
            } else {
                View::assign('chapter', $chapter);
                return view();
            }
        } catch (ModelNotFoundException $e) {
            abort(404);
        }
    }

    public function upload() {
        if (is_null(request()->file())) {
            return json([
                'code' => 1
            ]);
        } else {
            $cover = request()->file('file');
            $dir = 'article/tmp';
            $savename =str_replace ( '\\', '/',
                \think\facade\Filesystem::disk('public')->putFile($dir, $cover));
            return json([
                'code' => 0,
                'msg' => '',
                'txt' => $savename
            ]);
        }
    }

    public function delete()
    {
        $id = input('chapterid');
        try {
            $chapter = ArticleChapter::findOrFail($id);
            $chapter->delete();
            return ['err'=>0,'msg'=>'删除成功'];
        } catch (DataNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function content(){
        $articleid = input('articleid');
        $chapter_id = input('chapter_id');
        $bigId = floor((double)($articleid / 1000));
        $file = sprintf('/files/article/txt/%s/%s/%s.txt',
            $bigId, $articleid, $chapter_id);
        $content = $this->getTxtcontent($this->server . $file);
    }

    private function getTxtcontent($txtfile)
    {
        $client = app('httpclient');
        $res = $client->request('GET', $txtfile); //读取版本号
        $contents = $res->getBody();
        $content = '';
        $encoding = mb_detect_encoding($contents, array('GB2312', 'GBK', 'UTF-16', 'UCS-2', 'UTF-8', 'BIG5', 'ASCII'));
        $arr = explode("\n", $contents);
        $arr = array_filter($arr); //数组去空
        foreach ($arr as $str) {
            if ($encoding != false) {
                $str = iconv($encoding, 'UTF-8', $str);
                if ($str != "" and $str != NULL) {
                    $content = $content . '<p>' .  $str. '</p>';
                }
            } else {
                $str = mb_convert_encoding($str, 'UTF-8', 'Unicode');
                if ($str != "" and $str != NULL) {
                    $content = $content . '<p>' .  $str. '</p>';
                }
            }
        }
        return $content;
    }
}