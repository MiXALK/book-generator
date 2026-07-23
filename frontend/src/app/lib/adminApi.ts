const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

async function adminFetch<T>(
  token: string,
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const response = await fetch(`${apiBaseUrl}/admin${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
      ...(options.headers ?? {}),
    },
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.message || "Admin request failed.");
  }

  return data as T;
}

export function fetchReviewQueue(token: string) {
  return adminFetch<{ items: import("@/app/types/admin").ReviewQueueItem[] }>(token, "/review-queue");
}

export function fetchGoals(token: string) {
  return adminFetch<{ items: import("@/app/types/admin").StoryGoal[] }>(token, "/goals");
}

export function createGoal(token: string, body: { name: string; description?: string }) {
  return adminFetch<{ item: import("@/app/types/admin").StoryGoal }>(token, "/goals", {
    method: "POST",
    body: JSON.stringify(body),
  });
}

export function updateGoal(token: string, id: number, body: { name?: string; description?: string }) {
  return adminFetch<{ item: import("@/app/types/admin").StoryGoal }>(token, `/goals/${id}`, {
    method: "PUT",
    body: JSON.stringify(body),
  });
}

export function deleteGoal(token: string, id: number) {
  return adminFetch<{ message: string }>(token, `/goals/${id}`, { method: "DELETE" });
}

export function fetchTemplates(token: string) {
  return adminFetch<{ items: import("@/app/types/admin").BookTemplate[] }>(token, "/templates");
}

export function createTemplate(
  token: string,
  body: {
    title: string;
    is_free: boolean;
    template_type: string;
    story_goal_id?: number | null;
  },
) {
  return adminFetch<{ item: import("@/app/types/admin").BookTemplate }>(token, "/templates", {
    method: "POST",
    body: JSON.stringify(body),
  });
}

export function updateTemplate(
  token: string,
  id: number,
  body: Partial<{
    title: string;
    is_free: boolean;
    is_active: boolean;
    story_goal_id: number | null;
  }>,
) {
  return adminFetch<{ item: import("@/app/types/admin").BookTemplate }>(token, `/templates/${id}`, {
    method: "PUT",
    body: JSON.stringify(body),
  });
}

export function deleteTemplate(token: string, id: number) {
  return adminFetch<{ message: string }>(token, `/templates/${id}`, { method: "DELETE" });
}

export function submitTemplateReview(token: string, id: number) {
  return adminFetch<{ item: import("@/app/types/admin").BookTemplate }>(token, `/templates/${id}/submit-review`, {
    method: "POST",
  });
}

export function publishTemplate(token: string, id: number) {
  return adminFetch<{ item: import("@/app/types/admin").BookTemplate }>(token, `/templates/${id}/publish`, {
    method: "POST",
  });
}

export function fetchPrompts(token: string) {
  return adminFetch<{ items: import("@/app/types/admin").StoryPrompt[] }>(token, "/prompts");
}

export function createPrompt(
  token: string,
  body: {
    title: string;
    prompt_text: string;
    language: string;
    age_range?: string | null;
    story_goal_id?: number | null;
  },
) {
  return adminFetch<{ item: import("@/app/types/admin").StoryPrompt }>(token, "/prompts", {
    method: "POST",
    body: JSON.stringify(body),
  });
}

export function updatePrompt(
  token: string,
  id: number,
  body: Partial<{
    title: string;
    prompt_text: string;
    language: string;
    age_range: string | null;
    is_active: boolean;
  }>,
) {
  return adminFetch<{ item: import("@/app/types/admin").StoryPrompt }>(token, `/prompts/${id}`, {
    method: "PUT",
    body: JSON.stringify(body),
  });
}

export function deletePrompt(token: string, id: number) {
  return adminFetch<{ message: string }>(token, `/prompts/${id}`, { method: "DELETE" });
}

export function submitPromptReview(token: string, id: number) {
  return adminFetch<{ item: import("@/app/types/admin").StoryPrompt }>(token, `/prompts/${id}/submit-review`, {
    method: "POST",
  });
}

export function publishPrompt(token: string, id: number) {
  return adminFetch<{ item: import("@/app/types/admin").StoryPrompt }>(token, `/prompts/${id}/publish`, {
    method: "POST",
  });
}

export function ratePrompt(token: string, id: number, rating: number, notes?: string) {
  return adminFetch<{ item: import("@/app/types/admin").StoryPrompt }>(token, `/prompts/${id}/ratings`, {
    method: "POST",
    body: JSON.stringify({ rating, notes }),
  });
}

export function fetchPreview(token: string, type: string, id: number) {
  const path = type === "templates" ? `/templates/${id}/preview` : `/prompts/${id}/preview`;

  return adminFetch<import("@/app/types/admin").ContentPreview>(token, path);
}
