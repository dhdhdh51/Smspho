package com.smsdashboard.viewer;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;

public class BootReceiver extends BroadcastReceiver {
    @Override
    public void onReceive(Context context, Intent intent) {
        if (Intent.ACTION_BOOT_COMPLETED.equals(intent.getAction())) {
            String key = context.getSharedPreferences("sms_viewer", Context.MODE_PRIVATE)
                .getString("api_key", "");
            if (!key.isEmpty()) {
                Intent svc = new Intent(context, PollService.class);
                context.startForegroundService(svc);
            }
        }
    }
}
