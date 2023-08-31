<?php


namespace app\model;


use think\Model;

class Comments extends Model
{
    public function book()
    {
        return $this->belongsTo(ArticleArticle::class, 'articleid', 'articleid');
    }

    public function user()
    {
        return $this->belongsTo(SystemUsers::class, 'uid', 'uid');
    }

    public function getComments($order, $where, $num)
    {
        if ($num == 0) {
            $comments = Comments::with('user')->where($where)->order($order)->select();
        } else {
            if (strlen($num) == 3) {
                $arr = explode(',', $num);
                $comments = Comments::with('user')->where($where)
                    ->limit($arr[0], $arr[1])->order($order)->select();
            } else {
                $comments = Comments::with('user')->where($where)->limit($num)->order($order)->select();
            }
        }
        return $comments;
    }

    public function getPagedComments($order, $where, $pagesize)
    {
        $data = Comments::with('user')->where($where)->order($order)
            ->paginate([
                'list_rows' => $pagesize,
                'query' => request()->param(),
            ]);
        $arr = $data->toArray();
        $paged = array();
        $paged['comments'] = $arr['data'];
        $paged['page'] = [
            'total' => $arr['total'],
            'per_page' => $arr['per_page'],
            'current_page' => $arr['current_page'],
            'last_page' => $arr['last_page'],
            'query' => request()->param()
        ];
        return $paged;
    }
}