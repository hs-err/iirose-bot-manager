@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">机器人配置</div>

                <div class="card-body">
                    <form method="POST">
                        @csrf
                        用户名
                        <input type="text" name="username" value="{{ old('username')?:$bot->username }}"><br>
                        密码
                        <input type="text" name="password" value="{{ old('password')?:$bot->password }}"><br>
                        房间
                        <input type="text" name="room" value="{{ old('room')?:$bot->room }}"><br>
                        插件
                        <textarea name="plugins">{{ old('plugins')?:$bot->plugins }}</textarea><br>
                        配置
                        <textarea name="config">{{ old('config')?:$bot->config }}</textarea><br>
                        到期时间
                        <span>{{ strtotime($bot->end) > time()?$bot->end:'已到期' }}</span><br>
                        <button type="submit">提交</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
