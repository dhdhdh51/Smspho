package com.smsdashboard.viewer;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;

public class BootReceiver extends BroadcastReceiver {
    @Override
    public void onReceive(Context ctx, Intent intent) {
        if (!Intent.ACTION_BOOT_COMPLETED.equals(intent.getAction())) return;
        SharedPreferences prefs = ctx.getSharedPreferences("sms_viewer", Context.MODE_PRIVATE);
        if (prefs.getString("api_key", "").isEmpty()) return;
        Intent svc = new Intent(ctx, PollService.class);
        ctx.startForegroundService(svc);
    }
}
