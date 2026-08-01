import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { toggleLike } from '../api/catches';
import { useAuthStore } from '../stores/auth';

/**
 * Like state for one publication, shared by the feed card and the detail modal.
 *
 * The counters are local refs rather than the prop itself so the heart reacts
 * on the click instead of after the round trip, but they are kept in sync with
 * the source: the same publication is on screen twice (card underneath, modal
 * on top), and liking in one has to show in the other.
 *
 * @param source  getter returning the publication object
 * @param onChange called with {id, liked, likes_count} once the server answers
 */
export function useLike(source, onChange) {
    const router = useRouter();
    const authStore = useAuthStore();

    const liked = ref(false);
    const likesCount = ref(0);
    const pending = ref(false);

    // Watches the two fields, not the object: the pages update the publication
    // in place (`post.is_liked = …`), which leaves the reference untouched, so
    // watching the getter alone would never fire and the card under the modal
    // would keep showing the old heart.
    watch(
        () => [source()?.is_liked ?? false, source()?.likes_count ?? 0],
        ([isLiked, count]) => {
            liked.value = isLiked;
            likesCount.value = count;
        },
        { immediate: true }
    );

    async function toggle() {
        const post = source();
        if (!post || pending.value) return;

        if (!authStore.isAuthenticated) {
            router.push({ name: 'login' });

            return;
        }

        pending.value = true;
        liked.value = !liked.value;
        likesCount.value += liked.value ? 1 : -1;

        try {
            const result = await toggleLike(post.id);
            liked.value = result.liked;
            likesCount.value = result.likes_count;
            onChange?.({ id: post.id, liked: result.liked, likes_count: result.likes_count });
        } catch {
            liked.value = !liked.value;
            likesCount.value += liked.value ? 1 : -1;
        } finally {
            pending.value = false;
        }
    }

    return { liked, likesCount, pending, toggle };
}
