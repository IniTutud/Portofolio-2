create table if not exists public.portfolio_admins (
    user_id uuid primary key references auth.users (id) on delete cascade
);

alter table public.portfolio_admins enable row level security;
revoke all on table public.portfolio_admins from anon, authenticated;
grant select on table public.portfolio_admins to authenticated;

drop policy if exists "Admins can read their own role" on public.portfolio_admins;
create policy "Admins can read their own role"
    on public.portfolio_admins
    for select
    to authenticated
    using (user_id = (select auth.uid()));

create or replace function public.is_portfolio_admin()
returns boolean
language sql
stable
security definer
set search_path = ''
as $function$
    select exists (
        select 1
        from public.portfolio_admins
        where user_id = (select auth.uid())
    );
$function$;

revoke all on function public.is_portfolio_admin() from public;
grant execute on function public.is_portfolio_admin() to authenticated;

create table if not exists public.portfolio_content (
    id integer primary key check (id = 1),
    content jsonb check (content is null or jsonb_typeof(content) = 'object'),
    updated_at timestamptz not null default now()
);

alter table public.portfolio_content enable row level security;
revoke all on table public.portfolio_content from anon, authenticated;
grant select on table public.portfolio_content to anon, authenticated;
grant insert, update on table public.portfolio_content to authenticated;

drop policy if exists "Portfolio content is public" on public.portfolio_content;
create policy "Portfolio content is public"
    on public.portfolio_content
    for select
    to anon, authenticated
    using (true);

drop policy if exists "Portfolio admins can insert content" on public.portfolio_content;
create policy "Portfolio admins can insert content"
    on public.portfolio_content
    for insert
    to authenticated
    with check ((select public.is_portfolio_admin()));

drop policy if exists "Portfolio admins can update content" on public.portfolio_content;
create policy "Portfolio admins can update content"
    on public.portfolio_content
    for update
    to authenticated
    using ((select public.is_portfolio_admin()))
    with check ((select public.is_portfolio_admin()));

insert into public.portfolio_content (id, content)
values (1, null)
on conflict (id) do nothing;

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values (
    'portfolio-media',
    'portfolio-media',
    true,
    10485760,
    array['image/jpeg', 'image/png', 'image/webp', 'image/gif']::text[]
)
on conflict (id) do update
set public = excluded.public,
    file_size_limit = excluded.file_size_limit,
    allowed_mime_types = excluded.allowed_mime_types;

drop policy if exists "Portfolio admins can upload portfolio images" on storage.objects;
create policy "Portfolio admins can upload portfolio images"
    on storage.objects
    for insert
    to authenticated
    with check (
        bucket_id = 'portfolio-media'
        and (select public.is_portfolio_admin())
    );

-- After creating the CMS user under Authentication > Users, grant its UUID:
-- insert into public.portfolio_admins (user_id)
-- values ('PASTE_AUTH_USER_UUID_HERE')
-- on conflict (user_id) do nothing;
