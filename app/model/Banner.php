<?php


namespace app\model;


use think\Model;

class Banner extends Model
{
    public function book()
    {
        return $this->hasOne(ArticleArticle::class, 'articleid', 'articleid');
    }

    public function setTitleAttr($value)
    {
        return trim($value);
    }

    function getBanners($order, $where,$num)
    {
        $img_domain = config('site.img_domain');
        if ($num == 0) {
            $banners = Banner::with('book')
                ->order($order)->where($where)->select();
        } else {
            if (strlen($num) == 3) {
                $arr = explode(',',$num);
                $banners = Banner::where($where)
                    ->limit($arr[0],$arr[1])->order($order)->select();
            } else {
                $banners = Banner::with('book')->order($order)->where($where)->limit($num)->select();
            }
        }

        foreach ($banners as &$banner) {
            if (substr($banner['pic_name'], 0, strlen('http')) != 'http') {
                $banner['pic_name'] = $img_domain .  $banner['pic_name'];
            }
        }
        return $banners;
    }
}