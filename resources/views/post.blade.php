@extends('layouts.main')

@section('container')
    <article>
        <h2>{{ $post['title'] }}</h2>
        <h5>{{ $post['field'] }}</h5>
        <p>{{ $post['desc'] }}</p>
    </article>
    <a href="/blog">Back to Posts</a>
@endsection
