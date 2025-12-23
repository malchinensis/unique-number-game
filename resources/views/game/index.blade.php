<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('ゲーム一覧') }}
            </h2>
            <a href="{{ route('game.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                新しいゲームを作成
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($rooms->count() > 0)
                        <div class="grid gap-4">
                            @foreach($rooms as $room)
                                <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-lg font-semibold">{{ $room->name }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                作成者: {{ $room->creator->name }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                範囲: {{ $room->min_number }} - {{ $room->max_number }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                参加者: {{ $room->participants->count() }} / {{ $room->max_players }} 人
                                                (最小: {{ $room->min_players }} 人)
                                            </p>
                                        </div>
                                        <div class="flex flex-col items-end gap-2">
                                            <span class="px-3 py-1 rounded-full text-sm font-semibold
                                                @if($room->status === 'waiting') bg-yellow-200 text-yellow-800
                                                @elseif($room->status === 'playing') bg-green-200 text-green-800
                                                @else bg-gray-200 text-gray-800
                                                @endif">
                                                @if($room->status === 'waiting') 待機中
                                                @elseif($room->status === 'playing') プレイ中
                                                @else 終了
                                                @endif
                                            </span>
                                            <a href="{{ route('game.show', $room) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                詳細を見る
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                            現在進行中のゲームはありません。新しいゲームを作成してください！
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
