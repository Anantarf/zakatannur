# Chatbot RAG Quality Improvements — Progress Ledger
Plan: docs/superpowers/plans/2026-07-22-chatbot-rag-quality-improvements.md
Worktree: .worktrees/chatbot-rag-eval (branch: chatbot-rag-eval)
Base commit before Task 1: ce4eeb7

Task 1: complete (commits 38f26a5..cda923a, review clean — Approved).
  Scope note: mid-task, negative-case test surfaced a genuine pre-existing bug in
  KnowledgeRetriever::score() (substring-without-word-boundary + unfiltered generic
  Indonesian title-token words). User approved fixing it in-scope (commit cda923a).
  Minor (not blocking): task-1-report.md miscounts GENERIC_TITLE_WORDS as 23 (actual 26);
  the <4-char single-word-keyword floor is an unvalidated heuristic (only justified by "mal").

Task 2: complete (commit 3a31547, review clean — Approved, no findings).

Task 3: complete (commit 3ca8661, review clean — Approved, no findings).

Task 4: complete (no commit — verification-only, confirmed during plan-writing that
  KnowledgeBase::booted() at app/Models/KnowledgeBase.php:46-55 already auto-refreshes
  the embeddings cache synchronously on save/delete; no staleness bug existed).

Task 5: complete (commits 782e502..642fa67, review clean — Approved after one fix round).
  First-pass review found an Important finding: throttle description in CHATBOT_ZAKKY.md
  claimed a `throttle.chatbot`/ThrottleChatbot wiring that doesn't exist on this branch
  (controller error — plan's Task 5 Step 2 was written based on unrelated uncommitted
  changes on master that never made it into this branch). Fixed in 642fa67, re-reviewed
  clean: doc now correctly states throttle:50,1 (Laravel built-in) is applied directly,
  and documents ThrottleChatbot.php as unused dead code.

All 5 plan tasks complete. Proceeding to final whole-branch review.

Final whole-branch review: Ready to merge (Yes), one Important finding fixed (66e1c08):
  eval-rag exit code didn't cover fact-check failures, only retrieval - fixed.
  Minor findings logged, not actioned (low risk, no current case affected):
  - KnowledgeRetriever.php: <4-char keyword length floor may over-exclude future short
    domain keywords (e.g. "zis"); whole-word matching already covers the substring risk
    it was meant for.
  - GENERIC_TITLE_WORDS includes content words "cara"/"jadwal" alongside function words -
    could suppress a legitimate title-token signal if the KB grows without adding curated
    keywords for a new entry.
  - ChatbotGuardrailVerifierTest's bypass test will go red if the guardrail is later
    hardened - expected/correct, but worth a comment noting that's not a regression.
  - rag-threshold-evaluation.md hardcodes "20"/"7" case counts in prose - will drift if
    ChatbotEvalDataset changes without updating the doc.

BRANCH COMPLETE. Final commit: 66e1c08.
