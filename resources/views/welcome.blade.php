<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ config('app.name', 'iirose-bot-manager') }}</title>
  <link rel="stylesheet" href="{{ url('/mdui/css/mdui.min.css') }}">
  <script src="{{ url('/mdui/js/mdui.min.js') }}"></script>
  <link rel="stylesheet" href="{{ url('/css/index.css') }}">
</head>

<body oncontextmenu="return false;">
  <!--首屏-->
  <div class="index-home">
    <div class="index-home-bg">
      <div class="index-home-text mdui-typo">
        <p class="index-home-text-title">{{ config('app.name', 'iirose-bot-manager') }}</p>
      </div>
      <div class="index-home-btns">
          @auth
              <a href="{{ url('/home') }}" class="index-btn">HOME</a>
          @else
             <a href="{{ route('login') }}" class="index-btn">登录</a>
          @endauth
      </div>
    </div>
  </div>
</body>
<a href="http://www.beian.miit.gov.cn">鄂ICP备1801799号</a>
</html>
