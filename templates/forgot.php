<section class="narrow">
  <h1>Reset a password</h1>
  <p class="lede">We send a one-time reset link to campus mail. It's valid for an hour and works only once. If you already have the link, open it to get to the reset form.</p>
  <?php if (!empty($notice)): ?><p class="flash ok"><?= e($notice) ?></p><?php endif; ?>
  <form method="post" action="/forgot" class="stack">
    <label><span>Email</span><input type="email" name="email" required></label>
    <button type="submit">Request reset</button>
  </form>
  <p class="lede"><a href="/reset">I already have a token</a></p>
</section>
