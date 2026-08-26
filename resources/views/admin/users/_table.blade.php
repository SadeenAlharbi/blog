{{--
    One users table, rendered twice: once for moderators, once for members.
    Both sections carry exactly the same columns — user, email, role, articles,
    comments, joined, account state, actions.

    $rows        collection of users
    $emptyText   what to say when the section is empty
--}}
<div class="rounded-2xl border border-ink-100 bg-white overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[860px]">
            <thead>
                <tr class="bg-ink-25 text-ink-400 text-xs border-b border-ink-100">
                    <th class="px-4 py-3 font-medium text-start">المستخدم</th>
                    <th class="px-4 py-3 font-medium text-start">البريد الإلكتروني</th>
                    <th class="px-4 py-3 font-medium text-start">الدور</th>
                    <th class="px-4 py-3 font-medium text-start">المقالات</th>
                    <th class="px-4 py-3 font-medium text-start">التعليقات</th>
                    <th class="px-4 py-3 font-medium text-start">تاريخ التسجيل</th>
                    <th class="px-4 py-3 font-medium text-start">حالة الحساب</th>
                    <th class="px-4 py-3 font-medium text-start">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $user)
                    @php $isSelf = $user->id === auth()->id(); @endphp
                    <tr class="border-b border-ink-50 last:border-0 hover:bg-ink-25 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$user->name" :size="32" />
                                <a href="{{ route('admin.users.show', $user) }}" class="font-medium text-ink-800 hover:text-brand-700 truncate">
                                    {{ $user->name }}
                                    @if ($isSelf) <span class="text-xs text-ink-400">(أنت)</span> @endif
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-ink-500 truncate max-w-[200px]" dir="ltr">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if ($user->isSuperAdmin())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 text-white px-2.5 py-1 text-xs font-semibold">المشرف الرئيسي</span>
                            @elseif ($user->isAdmin())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-200 px-2.5 py-1 text-xs font-semibold">مشرف</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-ink-50 text-ink-600 ring-1 ring-ink-200 px-2.5 py-1 text-xs font-semibold">كاتب</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($user->posts_count) }}</td>
                        <td class="px-4 py-3 text-ink-500 tabular-nums">{{ number_format($user->comments_count) }}</td>
                        <td class="px-4 py-3 text-ink-500 whitespace-nowrap">{{ $user->created_at->format('Y/m/d') }}</td>
                        <td class="px-4 py-3">
                            @if ($user->is_active)
                                <x-status-badge status="published" label="نشط" />
                            @else
                                <x-status-badge status="hidden" label="معطّل" />
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 whitespace-nowrap">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-xs text-ink-500 hover:text-brand-700">عرض</a>

                                {{-- Both actions are gated by UserPolicy on the server;
                                     hiding the button is only the visible half. --}}
                                @can('updateRole', $user)
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-1"
                                          data-confirm="سيتم تغيير دور «{{ $user->name }}». تأكد من الصلاحيات الممنوحة."
                                          data-confirm-title="تغيير الدور" data-confirm-ok="تغيير">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="role" value="{{ $user->isAdmin() ? 'user' : 'admin' }}">
                                        <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-700">
                                            {{ $user->isAdmin() ? 'إزالة الإشراف' : 'ترقية لمشرف' }}
                                        </button>
                                    </form>
                                @endcan

                                @can('toggleActive', $user)
                                    <form method="POST" action="{{ route('admin.users.toggleActive', $user) }}"
                                          data-confirm="{{ $user->is_active ? 'سيتم تعطيل الحساب ومنع صاحبه من الوصول للوحة الإدارة.' : 'سيتم تفعيل الحساب.' }}"
                                          data-confirm-title="{{ $user->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}"
                                          data-confirm-ok="{{ $user->is_active ? 'تعطيل' : 'تفعيل' }}">
                                        @csrf
                                        <button type="submit" class="text-xs {{ $user->is_active ? 'text-red-500 hover:text-red-700' : 'text-brand-600 hover:text-brand-700' }}">
                                            {{ $user->is_active ? 'تعطيل' : 'تفعيل' }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-ink-400">{{ $emptyText }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
