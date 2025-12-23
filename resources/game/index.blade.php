<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                ゲームロビー
            </h2>
            <a href="{{ route('game.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                新規ゲーム作成
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($rooms->isEmpty())
                        <p class="text-gray-500 text-center py-8">現在参加可能なゲームはありません。</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($rooms as $room)
                                <div class="border rounded-lg p-4 hover:shadow-lg transition">
                                    <h3 class="font-bold text-lg mb-2">{{ $room->name }}</h3>
                                    <p class="text-sm text-gray-600 mb-2">作成者: {{ $room->creator->name }}</p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        範囲: {{ $room->min_number }} 〜 {{ $room->max_number }}
                                    </p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        参加者: {{ $room->participants->count() }} / {{ $room->max_players }}
                                    </p>
                                    <p class="text-sm mb-4">
                                        <span class="px-2 py-1 rounded text-white {{ $room->status === 'waiting' ? 'bg-green-500' : 'bg-yellow-500' }}">
                                            {{ $room->status === 'waiting' ? '募集中' : 'プレイ中' }}
                                        </span>
                                    </p>
                                    <a href="{{ route('game.show', $room) }}" class="block w-full text-center bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        詳細を見る
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
