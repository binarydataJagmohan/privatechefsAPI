<form method="post">
    @csrf
    
    <input type="hidden" name="email" value="{{ $user->email }}">
    <input type="password" name="password" placeholder="Password">
    <br><br>
    <input type="password" name="password_confirmation" placeholder="Confirm Password">
    <br><br>
    <input type="submit" value="Change Password">
</form>
