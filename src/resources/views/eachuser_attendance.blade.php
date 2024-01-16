@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="title">
    <a href="/users">ユーザー一覧に戻る</a>
    <p>{{ $user['name'] ?? '' }}さんの勤怠実績です</p>
</div>
<div class="attendance-content">
    <div class="attendance-table">
        <table class="attendance-table__inner">
            <tr class="attendance-table__row">
                <th class="attendance-table__header">日付</th>
                <th class="attendance-table__header">勤務開始時刻</th>
                <th class="attendance-table__header">勤務終了時刻</th>
                <th class="attendance-table__header">休憩時間(min)</th>
                <th class="attendance-table__header">勤務時間(hr)</th>
            </tr>
            @foreach ($items as $item)
            <tr class="attendance-table__row">
                <td class="attendance-table__item">{{ $item['year'] }}年{{ $item['month'] }}月{{ $item['day'] }}日</td>
                <td class="attendance-table__item">{{ (new DateTime($item['checkIn']))->format('H:i') }}</td>
                <td class="attendance-table__item">{{ (new DateTime($item['checkOut']))->format('H:i') }}</td>
                <td class="attendance-table__item">{{ $item['breakTime'] }}</td>
                <td class="attendance-table__item">{{ sprintf('%.2f', $item['workTime']) }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@if($items->total() > 0)
<div class="pagination">
    <p>{{ $items->total() }}件中{{ $items->firstItem() }}〜{{ $items->lastItem() }} 件を表示</p>
</div>
<div class="pagination__indicator">
    <p>{{ $items->links() }}</p>
</div>
@endif
@endsection