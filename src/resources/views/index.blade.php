@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="greeting">
  <p class="greeting__words">
    さんお疲れ様です！
  </p>
</div>

<div class="attendance__content">
  <div class="attendance__panel">
    <form class="attendance__button">
      <button class="attendance__button-submit" type="submit">勤務開始</button>
    </form>
    <form class="attendance__button">
      <button class="attendance__button-submit" type="submit">勤務終了</button>
    </form>
  </div>
  <div class="break__panel">
    <form class="break__button">
      <button class="break__button-submit" type="submit">休憩開始</button>
    </form>
    <form class="break__button">
      <button class="break__button-submit" type="submit">休憩終了</button>
    </form>
  </div>
</div>
@endsection