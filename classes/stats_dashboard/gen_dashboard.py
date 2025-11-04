import os
import altair as alt
import pandas as pd
import numpy as np
from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer
from collections import Counter
import re
import nltk

nltk.download('vader_lexicon', quiet=True)
alt.data_transformers.disable_max_rows()

# paths
file_path = "classes/stats_dashboard/new_dataset124.xlsx"
save_path = "pix/stats_dashboard.html" # output html for moodle iframe
os.makedirs(os.path.dirname(save_path), exist_ok=True)

# read and clean data
self_df = pd.read_excel(file_path, sheet_name="self")
peer_df = pd.read_excel(file_path, sheet_name="peer")
self_df.columns = self_df.columns.str.strip()
peer_df.columns = peer_df.columns.str.strip()

# detect numeric cols
numeric_cols_self = [c for c in self_df.columns if c.strip().upper().startswith('Q')]
numeric_cols_peer = [c for c in peer_df.columns if c.strip().upper().startswith('Q')]

# convert to numeric
for df, cols in [(self_df, numeric_cols_self), (peer_df, numeric_cols_peer)]:
    for col in cols:
        df[col] = pd.to_numeric(df[col], errors='coerce')

# sentiment analysis
text_cols = ['Brief Desceiption', 'Personal Refelction']
analyzer = SentimentIntensityAnalyzer()

def get_sentiment_label(text):
    text = str(text)
    score = analyzer.polarity_scores(text)['compound']
    if score > 0.05:
        return 'Positive', score
    elif score < -0.05:
        return 'Negative', score
    else:
        return 'Neutral', score

for df in [self_df, peer_df]:
    for col in text_cols:
        df[col + '_sentiment_label'], df[col + '_sentiment_score'] = zip(
            *[get_sentiment_label(t) for t in df[col]]
        )

# aggregations
self_df['self_avg'] = self_df[numeric_cols_self].mean(axis=1)
peer_df['peer_individual_avg'] = peer_df[numeric_cols_peer].mean(axis=1)
peer_grouped = peer_df.groupby('Student Number').agg(peer_avg=('peer_individual_avg', 'mean')).reset_index()
merged_df = pd.merge(self_df, peer_grouped, on='Student Number', how='outer')
merged_df[['self_avg','peer_avg']] = merged_df[['self_avg','peer_avg']].fillna(0)

# CHARTS START HERE
alt.data_transformers.disable_max_rows()

## overall sentiment distribution pie
sent_counts = pd.concat([
    self_df['Brief Desceiption_sentiment_label'],
    peer_df['Brief Desceiption_sentiment_label']
]).value_counts().reset_index()
sent_counts.columns = ['Sentiment','Count']

pie_chart = alt.Chart(sent_counts).mark_arc(innerRadius=60).encode(
    theta='Count',
    color=alt.Color('Sentiment:N', scale=alt.Scale(scheme='tableau10')),
    tooltip=['Sentiment','Count']
).properties(
    title='Overall Sentiment Distribution (shows positivity/negativity of all feedback)',
    width=400, height=400
)

## top feedback keywords bar
all_feedback = (
    ' '.join(self_df['Brief Desceiption'].astype(str)) + ' ' +
    ' '.join(peer_df['Brief Desceiption'].astype(str))
)
words = re.findall(r'\b[a-zA-Z]{3,}\b', all_feedback.lower())
stopwords = set([
    'the','and','for','with','this','that','you','are','was','were','but','have',
    'had','has','our','your','they','their','them','his','her','its','from','what',
    'when','where','how','which','also','can','could','would','should','may','might'
])
filtered_words = [w for w in words if w not in stopwords]
word_freq = Counter(filtered_words).most_common(15)
word_df = pd.DataFrame(word_freq, columns=['Word', 'Count'])

keyword_chart = alt.Chart(word_df).mark_bar().encode(
    x=alt.X('Count:Q', title='Frequency'),
    y=alt.Y('Word:N', sort='-x', title='Top Words in Feedback'),
    color=alt.Color('Count:Q', scale=alt.Scale(scheme='blues')),
    tooltip=['Word','Count']
).properties(
    title='Most Common Feedback Keywords (Top 15)',
    width=400, height=400
)

## avg self vs peer scores bar chart
avg_df = merged_df[['Name','self_avg','peer_avg']].melt(
    id_vars='Name', var_name='Type', value_name='Average Score'
)
bar_chart = (
    alt.Chart(avg_df)
    .mark_bar()
    .encode(
        x=alt.X('Name:N', title='Student', axis=alt.Axis(labelAngle=-45)),
        y=alt.Y('Average Score:Q', title='Average Score', stack=None),
        color=alt.Color('Type:N', scale=alt.Scale(scheme='tableau10')),
        tooltip=['Name','Type','Average Score']
    )
    .properties(
        title='Average Self vs Peer Scores (comparison per student)',
        width=800, height=400
    )
)

## self vs peer scatter plot
scatter = (
    alt.Chart(merged_df)
    .mark_circle(size=100, opacity=0.8)
    .encode(
        x=alt.X('self_avg:Q', title='Average Self Score', scale=alt.Scale(domain=[2.5, 5])),
        y=alt.Y('peer_avg:Q', title='Average Peer Score', scale=alt.Scale(domain=[2, 4])),
        color=alt.Color('Team Name/Number:N', title='Team'),
        tooltip=['Name','Team Name/Number','self_avg','peer_avg']
    )
    .properties(
        title='Self vs Peer Grades (each dot represents one student)',
        width=700, height=400
    )
)


## team avg scores
team_avg = merged_df.groupby('Team Name/Number').agg(
    avg_self=('self_avg','mean'),
    avg_peer=('peer_avg','mean')
).reset_index()
team_bar = alt.Chart(team_avg).mark_bar().encode(
    x=alt.X('Team Name/Number:N', title='Team'),
    y=alt.Y('avg_self:Q', title='Average Self Score'),
    color=alt.value('#4C78A8'),
    tooltip=['Team Name/Number','avg_self','avg_peer']
).properties(
    title='Team Average Scores (mean self & peer evaluations per team)',
    width=800, height=350
)

## self v peer score gap
merged_df['gap'] = merged_df['self_avg'] - merged_df['peer_avg']
gap_chart = alt.Chart(merged_df).mark_bar().encode(
    x=alt.X('gap:Q', title='Self - Peer Score Difference'),
    y=alt.Y('Name:N', sort='-x'),
    color=alt.condition(
        alt.datum.gap > 0, alt.value('steelblue'), alt.value('tomato')
    ),
    tooltip=['Name','self_avg','peer_avg','gap']
).properties(
    title='Self vs Peer Score Gap (who over/underestimates themselves)',
    width=800, height=400
)

# COMBINE CHARTS INTO DASHBOARD

# row 1: sentiment pie + keywords bar
row1 = (
    alt.hconcat(
        pie_chart.properties(width=300, height=350),
        keyword_chart.properties(width=380, height=350)
    )
    .resolve_scale(color='independent')
    .properties(title=' ')
)

# row 2 onwards one chart per row
row2 = bar_chart.properties(width=700, height=400)
row3 = scatter.properties(width=700, height=400)
row4 = team_bar.properties(width=700, height=350)
row5 = gap_chart.properties(width=700, height=400)

dashboard = (
    alt.vconcat(
        row1, row2, row3, row4, row5,
        spacing=120
    )
    .configure_title(fontSize=18, anchor='start', color='#2c3e50')
    .configure_axis(labelFontSize=12, titleFontSize=16)
    .configure_view(strokeWidth=0)
    .configure(background='#fafafa')
    .properties(
        title='SMARTSPE STUDENT EVALUATION ANALYTICS DASHBOARD'
    )
    .configure_view(continuousWidth=700, continuousHeight=400)
)

dashboard.save(save_path)
print(f"Dashboard saved to: {save_path}")
