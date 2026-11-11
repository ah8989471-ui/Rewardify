# Connecting CPAGrip

## 1. Offerwall (what users see)

1. In CPAGrip, open your **Offerwall / Content Locker** settings and copy
   your offerwall URL.
2. In **WatchEarn Admin → CPAGrip**, paste it into **Offerwall URL**.
   Wherever CPAGrip expects the user id (usually the `subid` parameter),
   write `{USER_ID}` — WatchEarn replaces it with each logged-in user's
   id automatically, e.g.:

   ```
   https://www.cpagrip.com/common/offerwall.php?user_id=12345&subid={USER_ID}
   ```

   Alternatively paste a complete iframe embed code into **Iframe embed
   code** (it supports the same `{USER_ID}` placeholder and wins over the
   URL if both are set).
3. Users now see the wall under **Offerwall** in their panel, and a banner
   on the **Watch Ads / Tasks** page.

## 2. Postback (how users get paid)

1. In **WatchEarn Admin → CPAGrip** you will see **Your postback URL**,
   ready to copy. It looks like:

   ```
   https://your-domain.com/postback/cpagrip?key=<SECRET>&user_id={subid}&payout={payout}&txn_id={transaction_id}&offer_id={offer_id}
   ```

2. Paste it into CPAGrip's postback settings for the same offerwall.
3. Keep the `<SECRET>` in the URL identical to the **Postback secret key**
   in WatchEarn admin. If they differ, postbacks are rejected (and logged
   as `rejected`). Use **Regenerate secret** only together with updating
   CPAGrip.

Behaviour:

- A valid postback credits the user's wallet instantly (kind:
  Offerwall/CPAGrip) and pays the referrer their commission.
- The same `txn_id` is never credited twice — CPAGrip retries are logged
  as `duplicate` and return HTTP 200 so CPAGrip stops retrying.
- Every call is visible in **Admin → CPAGrip → Recent postbacks**.

## 3. Testing the postback locally

With the app running, open this in a browser (replace the secret with the
one from your admin page and `1` with the demo user's id):

```
http://localhost:8000/postback/cpagrip?key=<SECRET>&user_id=2&payout=0.50&txn_id=TEST-001&offer_id=DEMO
```

You should see `OK`, and $0.50 should appear in that user's wallet.
Opening the same URL again returns `duplicate` and does not pay twice.
