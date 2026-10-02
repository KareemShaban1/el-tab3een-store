@if(!empty($notifications_data))
  @foreach($notifications_data as $notification_data)
    @php
      $destination = $notification_data['link'] ?? '#';
      $notificationId = $notification_data['notification_id'] ?? null;
      $isUnread = empty($notification_data['read_at']);
      $isPopup = isset($notification_data['show_popup']);

      // Server-side mark-as-read + redirect (does not depend on JS / cached app.js).
      if (! $isPopup && $isUnread && ! empty($notificationId) && $destination !== '#') {
          $href = route('notifications.open', $notificationId).'?redirect='.urlencode($destination);
      } else {
          $href = $destination;
      }
    @endphp
    <li class="@if($isUnread) unread @endif notification-li tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-600 tw-transition-all tw-duration-200 tw-rounded-lg hover:tw-text-gray-900 hover:tw-bg-gray-100">
      <a href="{{ $href }}"
      @if(!empty($notificationId)) data-notification-id="{{ $notificationId }}" @endif
      @if($isPopup) class="show-notification-in-popup" @endif >
        <i class="notif-icon {{$notification_data['icon_class'] ?? ''}}"></i> 
        <span class="notif-info">{!! $notification_data['msg'] ?? '' !!}</span>
        <span class="time">{{$notification_data['created_at']}}</span>
      </a>
    </li>
  @endforeach
@else
  <li class="text-center no-notification notification-li">
    @lang('lang_v1.no_notifications_found')
  </li>
@endif
